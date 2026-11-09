#!/usr/bin/env python3
"""Build an allowlisted LOCAL staging directory. No network, Git mutation, or dotenv access."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('output', type=Path, help='New staging directory outside public/')
parser.add_argument('--archive', action='store_true', help='Verify and archive an existing prepared directory after offline production Composer verification')
args = parser.parse_args()
output = args.output.resolve()
if output == ROOT or ROOT in output.parents and output.relative_to(ROOT).parts[0] not in ('qa-artifacts',):
    raise SystemExit('Use a new ignored qa-artifacts/ directory or an external staging directory.')
if args.archive:
    if not (output/'vendor/composer/installed.json').is_file():
        raise SystemExit('Production vendor not verified/prepared.')
    installed = json.loads((output/'vendor/composer/installed.json').read_text())
    if installed.get('dev', True) or installed.get('dev-package-names'):
        raise SystemExit('Refusing release with development Composer packages.')
    if (output/'public/hot').exists() or (output/'public/storage').exists():
        raise SystemExit('Refusing development hot file or storage symlink.')
    for path in output.rglob('*'):
        if path.is_symlink() or path.name in {'.env', '.git', '.agents', '.codex', '.aws', 'auth.json'} or path.suffix in {'.sqlite', '.sql', '.log', '.pem', '.key'}:
            raise SystemExit('Unsafe release entry; details withheld.')
    for path in (output/'bootstrap/cache').iterdir():
        if path.name != '.gitignore':
            raise SystemExit('Clear all machine-specific bootstrap caches before archive.')
    for directory in ['storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache/data', 'storage/logs']:
        if any(p.is_file() and p.name != '.gitignore' for p in (output/directory).rglob('*')):
            raise SystemExit('Clear release staging runtime files before archive.')
    archive = output.with_suffix('.zip')
    if archive.exists():
        raise SystemExit('Archive already exists; refusing overwrite.')
    with zipfile.ZipFile(archive, 'x', zipfile.ZIP_DEFLATED) as bundle:
        for path in sorted(output.rglob('*')):
            if path.is_file():
                bundle.write(path, path.relative_to(output))
    print(json.dumps({'archive': str(archive), 'bytes': archive.stat().st_size,
                      'sha256': hashlib.sha256(archive.read_bytes()).hexdigest()}))
else:
    if output.exists():
        raise SystemExit('Staging exists; refusing overwrite.')
    output.mkdir(parents=True, mode=0o700)
    for directory in ['app', 'config', 'routes', 'resources', 'lang', 'database/migrations']:
        shutil.copytree(ROOT/directory, output/directory)
    for name in ['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js', '.env.production.example']:
        if (ROOT/name).is_file():
            shutil.copy2(ROOT/name, output/name)
    for name in ['app.php', 'providers.php']:
        (output/'bootstrap').mkdir(exist_ok=True)
        shutil.copy2(ROOT/'bootstrap'/name, output/'bootstrap'/name)
    for directory in ['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs']:
        (output/directory).mkdir(parents=True, exist_ok=True)
        (output/directory/'.gitignore').write_text('*\n!.gitignore\n')
    for name in ['build', 'images', 'index.php', '.htaccess', 'robots.txt', 'favicon.ico']:
        source = ROOT/'public'/name
        if source.is_dir():
            shutil.copytree(source, output/'public'/name)
        elif source.is_file():
            (output/'public').mkdir(exist_ok=True)
            shutil.copy2(source, output/'public'/name)
    if not (ROOT/'vendor/composer/installed.json').is_file():
        raise SystemExit('Existing dependencies unavailable; no automatic download.')
    shutil.copytree(ROOT/'vendor', output/'vendor')
    identity = {'source_head': subprocess.check_output(['git','rev-parse','HEAD'], cwd=ROOT, text=True).strip(),
                'working_tree_source': True, 'note': 'Staged working tree; run offline no-dev Composer, platform/cache checks, then --archive.'}
    (output/'release-source.json').write_text(json.dumps(identity, indent=2)+'\n')
    print('Allowlisted staging prepared. No dotenv, DB, credentials, tests, WSL scripts, runtime cache or logs copied.')
