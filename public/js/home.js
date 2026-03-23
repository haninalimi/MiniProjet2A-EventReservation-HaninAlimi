(function() {
    const phrases = [
        'Concerts · Conférences · Workshops',
        'Réservez vos places en 30 secondes',
        'Technologie Passkey FIDO2',
        '+ de 500 événements chaque année'
    ];
    let pi = 0, ci = 0, del = false;
    const el = document.getElementById('tw-text');
    if (!el) return;
    
    function tick() {
        const ph = phrases[pi];
        if (!del) { 
            ci++; 
            el.textContent = ph.slice(0, ci); 
            if (ci === ph.length) {
                del = true;
                setTimeout(tick, 2200);
                return;
            }
        } else { 
            ci--; 
            el.textContent = ph.slice(0, ci); 
            if (ci === 0) {
                del = false;
                pi = (pi + 1) % phrases.length;
                setTimeout(tick, 350);
                return;
            }
        }
        setTimeout(tick, del ? 30 : 60);
    }
    setTimeout(tick, 1600);

    setInterval(() => {
        const seats = document.getElementById('hero-seats');
        if (seats) {
            const cur = parseInt(seats.textContent) || 157;
            const chg = Math.random() < 0.3 ? -1 : 0;
            seats.textContent = Math.max(0, cur + chg);
        }
        document.querySelectorAll('.rt-value').forEach(el => {
            const cur = parseInt(el.textContent) || 0;
            const chg = Math.random() < 0.2 ? (Math.random() < 0.5 ? -1 : 0) : 0;
            el.textContent = Math.max(0, cur + chg);
        });
    }, 4000);
})();