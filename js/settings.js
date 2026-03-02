/**
 * settings.js — aplica la configuración del sitio desde la API al DOM.
 * Expone window.getSettings() para otros scripts de página.
 */

const _settingsPromise = (async () => {
    try {
        const res = await fetch(`${API_BASE}/settings/index.php`);
        return res.ok ? await res.json() : {};
    } catch { return {}; }
})();

window.getSettings = () => _settingsPromise;

_settingsPromise.then(cfg => {
    if (!cfg) return;

    // --- Nombre del sitio: reemplaza todo texto "The Palatin" en la página ---
    if (cfg.site_name) {
        document.title = document.title.replace(/The Palatin/g, cfg.site_name);
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => {
            if (node.textContent.includes('The Palatin')) {
                node.textContent = node.textContent.replace(/The Palatin/g, cfg.site_name);
            }
        });
    }

    // --- Logo vs nombre: muestra uno u otro según configuración ---
    const navLogo    = document.getElementById('site-logo-nav');
    const navName    = document.getElementById('site-name-nav');
    const footerLogo = document.getElementById('site-logo-footer');
    const footerName = document.getElementById('site-name-footer');

    if (cfg.site_logo) {
        if (navLogo)    { navLogo.src = cfg.site_logo; navLogo.alt = cfg.site_name; navLogo.style.display = ''; }
        if (navName)    navName.style.display    = 'none';
        if (footerLogo) { footerLogo.src = cfg.site_logo; footerLogo.alt = cfg.site_name; footerLogo.style.display = ''; }
        if (footerName) footerName.style.display = 'none';
    } else {
        if (navLogo)    navLogo.style.display    = 'none';
        if (navName)    navName.style.display     = '';
        if (footerLogo) footerLogo.style.display  = 'none';
        if (footerName) footerName.style.display  = '';
    }

    // --- Descripción del footer ---
    const footerDesc = document.getElementById('site-description');
    if (footerDesc && cfg.site_description) {
        footerDesc.textContent = cfg.site_description;
    }

    // --- Redes sociales ---
    const socialDiv = document.querySelector('.footer-social-info');
    if (socialDiv) {
        const links = [];
        if (cfg.social_facebook)  links.push(`<a href="${cfg.social_facebook}"  target="_blank" rel="noopener"><span class="fa fa-facebook"></span></a>`);
        if (cfg.social_instagram) links.push(`<a href="${cfg.social_instagram}" target="_blank" rel="noopener"><span class="fa fa-instagram"></span></a>`);
        if (cfg.social_twitter)   links.push(`<a href="${cfg.social_twitter}"   target="_blank" rel="noopener"><span class="fa fa-twitter"></span></a>`);
        if (cfg.social_telegram)  links.push(`<a href="${cfg.social_telegram}"  target="_blank" rel="noopener"><span class="fa fa-telegram"></span></a>`);
        if (cfg.whatsapp)         links.push(`<a href="https://wa.me/${cfg.whatsapp}" target="_blank" rel="noopener"><span class="fa fa-whatsapp"></span></a>`);
        if (links.length > 0) socialDiv.innerHTML = links.join('');
    }
});
