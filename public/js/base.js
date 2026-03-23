import { TokenManager, getCurrentUser, logout } from '/js/auth.js';

window.handleLogout = () => logout();

document.addEventListener('DOMContentLoaded', async () => {
    if (TokenManager.isAuthenticated()) {
        document.getElementById('nav-guest-zone').style.display = 'none';
        const uz = document.getElementById('nav-user-zone');
        uz.style.display = 'flex';
        try {
            const u = await getCurrentUser();
            document.getElementById('nav-email-txt').textContent = u.email;
        } catch(e) { TokenManager.clear(); }
    }
});

(function() {
    const canvas = document.getElementById('luxury-canvas');
    const ctx = canvas.getContext('2d');
    let W, H;
    function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
    resize(); window.addEventListener('resize', resize);

    const pts = Array.from({length:55}, () => ({
        x: Math.random()*window.innerWidth,
        y: Math.random()*window.innerHeight,
        r: Math.random()*1.3+.3,
        dx: (Math.random()-.5)*.2,
        dy: (Math.random()-.5)*.2,
        o: Math.random()*.3+.08
    }));

    function drawPts() {
        ctx.clearRect(0,0,W,H);
        pts.forEach(p => {
            ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
            ctx.fillStyle = `rgba(197,165,114,${p.o})`; ctx.fill();
            p.x+=p.dx; p.y+=p.dy;
            if(p.x<0||p.x>W) p.dx*=-1;
            if(p.y<0||p.y>H) p.dy*=-1;
        });
        requestAnimationFrame(drawPts);
    }
    drawPts();

    const html = document.documentElement;
    const themeBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    let isDark = localStorage.getItem('theme') !== 'light';

    function applyTheme() {
        html.setAttribute('data-theme', isDark ? 'dark' : 'light');
        themeIcon.className = isDark ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }
    applyTheme();

    themeBtn.addEventListener('click', () => { isDark = !isDark; applyTheme(); });

    const langBtn = document.getElementById('langBtn');
    const langDropdown = document.getElementById('langDropdown');
    const langLabel = document.getElementById('langLabel');
    let currentLang = localStorage.getItem('lang') || 'fr';

    function setLang(lang) {
        currentLang = lang;
        langLabel.textContent = lang.toUpperCase();
        localStorage.setItem('lang', lang);
        document.querySelectorAll('.lang-opt').forEach(el => {
            el.classList.toggle('active', el.dataset.lang === lang);
        });
        langDropdown.classList.remove('open');

        const msgs = {
            fr: { events:'Événements', how:'Comment ça marche' },
            en: { events:'Events', how:'How it works' },
            ar: { events:'الفعاليات', how:'كيف يعمل' }
        };
        const m = msgs[lang] || msgs.fr;
        const navLinks = document.querySelectorAll('.nav-link');
        if(navLinks[0]) navLinks[0].textContent = m.events;
        if(navLinks[1]) navLinks[1].textContent = m.how;
    }
    window.setLang = setLang;
    setLang(currentLang);

    langBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        langDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => langDropdown.classList.remove('open'));

    const sections = document.querySelectorAll('section[data-sec]');
    const scrollTrack = document.getElementById('scrollTrack');
    const bookmarkTrack = document.getElementById('bookmarkTrack');
    const labels = ['Couverture','Chapitre I','Chapitre II','Chapitre III','Marginalia','Colophon'];

    sections.forEach((s, i) => {
        const dot = document.createElement('div');
        dot.className = 's-dot' + (i===0?' active':'');
        dot.onclick = () => s.scrollIntoView({behavior:'smooth'});
        scrollTrack.appendChild(dot);

        const mark = document.createElement('div');
        mark.className = 'b-mark' + (i===0?' active':'');
        mark.onclick = () => s.scrollIntoView({behavior:'smooth'});
        mark.innerHTML = `<span class="b-label">${labels[i]||'§'}</span>`;
        bookmarkTrack.appendChild(mark);
    });

    const io = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(e.isIntersecting) {
                const idx = parseInt(e.target.dataset.sec);
                document.querySelectorAll('.s-dot').forEach((d,i)=>d.classList.toggle('active',i===idx));
                document.querySelectorAll('.b-mark').forEach((d,i)=>d.classList.toggle('active',i===idx));
            }
        });
    }, {threshold:.4});
    sections.forEach(s => io.observe(s));

    const ro = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
    }, {threshold:.07});
    document.querySelectorAll('.reveal-lux').forEach(el => ro.observe(el));

    const co = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(e.isIntersecting) {
                const el = e.target, target = parseInt(el.dataset.target), suffix = el.dataset.suffix||'';
                let cur = 0; const step = Math.ceil(target/55);
                const t = setInterval(()=>{
                    cur = Math.min(cur+step, target);
                    el.textContent = cur+suffix;
                    if(cur>=target) clearInterval(t);
                }, 22);
                co.unobserve(el);
            }
        });
    }, {threshold:.5});
    document.querySelectorAll('[data-target]').forEach(el => co.observe(el));

    window.addEventListener('scroll', () => {
        document.getElementById('navbar').style.height = window.scrollY>60 ? '56px' : '68px';
    });

    document.querySelectorAll('.toast-lux').forEach(t => {
        setTimeout(()=>{ t.style.opacity='0'; t.style.transition='opacity .4s'; setTimeout(()=>t.remove(),400); }, 4000);
    });

})();