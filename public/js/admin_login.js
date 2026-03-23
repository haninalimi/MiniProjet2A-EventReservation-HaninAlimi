(function() {
    const html = document.documentElement;
    const btn  = document.getElementById('themeBtn');
    const icon = document.getElementById('themeIcon');
    let dark = localStorage.getItem('adm-theme') !== 'light';

    function apply() {
        html.setAttribute('data-theme', dark ? 'dark' : 'light');
        icon.className = dark ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
    }
    apply();
    btn.addEventListener('click', () => { 
        dark = !dark; 
        localStorage.setItem('adm-theme', dark ? 'dark' : 'light'); 
        apply(); 
    });
})();