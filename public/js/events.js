function filterEvents() {
    const q = document.getElementById('evSearch').value.toLowerCase();
    const items = document.querySelectorAll('.event-item');
    let visible = 0;
    items.forEach(item => {
        const title = item.dataset.title || '';
        const match = title.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const vc = document.getElementById('visibleCount');
    if (vc) vc.textContent = visible;
}

function applyFilter(type) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    document.getElementById('f-' + type)?.classList.add('active');
    const today = new Date().toISOString().split('T')[0];
    const items = document.querySelectorAll('.event-item');
    let visible = 0;
    items.forEach(item => {
        let show = true;
        if (type === 'upcoming') show = item.dataset.date >= today;
        if (type === 'available') show = parseInt(item.dataset.seats) > 0;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const vc = document.getElementById('visibleCount');
    if (vc) vc.textContent = visible;
}