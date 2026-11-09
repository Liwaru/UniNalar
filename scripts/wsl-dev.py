#!/usr/bin/env python3
"""Prepare/start only the isolated WSL runtime. Never reads Groq credential values."""
import base64
import json
import os
from pathlib import Path
import secrets
import socket
import sqlite3
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
MANIFEST = ROOT / 'storage/app/private/wsl-dev-runtime.json'


def docker(*args):
    return subprocess.check_output(['docker', *args], text=True).strip()


if sys.argv[1:] == ['prepare']:
    if MANIFEST.exists():
        raise SystemExit('Existing development manifest: use start; refusing to replace database/key.')
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 8140))
    if (ROOT / 'bootstrap/cache/config.php').exists():
        raise SystemExit('Cached config exists; review it before preparation.')
    # Keep all unrelated lines, including the opaque Groq key line, byte-for-byte.
    lines = (ROOT / '.env').read_bytes().splitlines(keepends=True)
    for line in lines:
        if line.startswith(b'APP_KEY=') and line.partition(b'=')[2].strip().strip(b'\"\''):
            raise SystemExit('Existing APP_KEY: review encrypted-data dependencies before replacement.')
    tag = secrets.token_hex(8)
    db = ROOT / 'database' / ('wsl-dev-' + tag + '.sqlite')
    fd = os.open(db, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    os.close(fd)
    with sqlite3.connect(f'file:{db}?mode=ro', uri=True) as conn:
        assert conn.execute('select count(*) from sqlite_master').fetchone()[0] == 0
        assert Path(conn.execute('pragma database_list').fetchone()[2]).resolve() == db
    config = dict(APP_NAME='UniNalar', APP_ENV='local', APP_DEBUG='true',
                  APP_URL='http://localhost:8140', APP_LOCALE='id', APP_FALLBACK_LOCALE='en',
                  APP_KEY='base64:' + base64.b64encode(secrets.token_bytes(32)).decode(),
                  DB_CONNECTION='sqlite', DB_DATABASE=str(db), DB_URL='', DB_HOST='',
                  DB_PORT='', DB_USERNAME='', DB_PASSWORD='', DB_SOCKET='',
                  SESSION_DRIVER='file', CACHE_STORE='file', QUEUE_CONNECTION='sync',
                  SESSION_COOKIE='uninalar_wsl_dev_session', GROQ_LIVE_ENABLED='false')
    out = []
    seen = set()
    for line in lines:
        name = line.partition(b'=')[0].decode(errors='ignore').strip()
        if name in config:
            if name not in seen:
                out.append((name + '=' + config[name] + '\n').encode())
                seen.add(name)
        else:
            out.append(line)
    if out and not out[-1].endswith(b'\n'):
        out[-1] += b'\n'
    for name in config.keys() - seen:
        out.append((name + '=' + config[name] + '\n').encode())
    temp = ROOT / '.env.wsl-edit'
    with temp.open('xb') as f:
        os.chmod(temp, 0o600)
        f.write(b''.join(out))
    temp.replace(ROOT / '.env')
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    MANIFEST.write_text(json.dumps(dict(root=str(ROOT), database=str(db), tag=tag,
                                      initialized=False, synthetic_student_ids=[]), indent=2))
    print('Prepared new empty SQLite and unique development key; Groq line preserved. Manifest:', MANIFEST)
elif sys.argv[1:] == ['start']:
    m = json.loads(MANIFEST.read_text())
    assert m['root'] == str(ROOT)
    assert Path(m['database']).is_file()
    name = 'uninalar-wsl-dev-' + m['tag']
    network = name + '-net'
    names = docker('ps', '-a', '--format', '{{.Names}}').splitlines()
    if name in names:
        raise SystemExit('Runtime already exists; inspect it instead of recreating.')
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 8140))
    docker('image', 'inspect', 'uninalar-app:latest', '--format', '{{.Id}}')
    docker('network', 'create', '--label', 'uninalar.wsl.dev=' + m['tag'], network)
    docker('run', '-d', '--name', name, '--label', 'uninalar.wsl.dev=' + m['tag'],
           '--network', network, '--user', str(os.getuid()) + ':' + str(os.getgid()),
           '--cap-drop', 'ALL', '--security-opt', 'no-new-privileges',
           '-p', '127.0.0.1:8140:8000', '-v', str(ROOT) + ':' + str(ROOT), '-w', str(ROOT),
           '--entrypoint', 'php', 'uninalar-app:latest', '-S', '0.0.0.0:8000',
           '-t', 'public', 'scripts/wsl-router.php')
    print('Isolated development runtime:', name, 'http://localhost:8140 (live AI disabled by configuration)')
else:
    raise SystemExit('Usage: python3 scripts/wsl-dev.py prepare|start')
