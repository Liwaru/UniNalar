// A hint is useful only when this particular table actually overflows.
for (const table of document.querySelectorAll('.owner-monitoring .admin-table-wrap')) {
    const hint = document.createElement('p');
    hint.className = 'owner-data-note owner-scroll-hint';
    hint.textContent = 'Geser tabel untuk melihat kolom lainnya. Dengan keyboard, fokuskan tabel lalu gunakan tombol panah.';
    hint.hidden = true;
    table.before(hint);
    const update = () => { hint.hidden = table.scrollWidth > table.clientWidth + 1 ? false : true; };
    const observer = new ResizeObserver(update);
    observer.observe(table);
    const content = table.querySelector('table');
    if (content) observer.observe(content);
    update();
}
