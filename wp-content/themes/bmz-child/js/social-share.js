'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('socialShareContainer');
    if (!container) return;

    const currentUrl = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);

    const socialNetworks = [
        { name: 'Facebook', icon: 'facebook', prefix: 'fab fa-', url: `https://www.facebook.com/sharer.php?u=${currentUrl}&t=${title}` },
        { name: 'X\\Twitter', icon: 'twitter', prefix: 'fab fa-x-', url: `https://x.com/intent/tweet?url=${currentUrl}&text=${title}&hashtags=bestMZ` },
        { name: 'LinkedIn', icon: 'linkedin', prefix: 'fab fa-', url: `https://www.linkedin.com/shareArticle?mini=true&url=${currentUrl}&title=${title}` },
        { name: 'WhatsApp', icon: 'whatsapp', prefix: 'fab fa-', url: `https://wa.me/?text=${title} ${currentUrl}` },
        { name: 'Telegram', icon: 'telegram', prefix: 'fab fa-', url: `https://t.me/share/url?url=${currentUrl}&text=${title}` },
        { name: 'VKontakte', icon: 'vk', prefix: 'fab fa-', url: `https://vk.com/share.php?url=${currentUrl}` },
        { name: 'Odnoklassniki', icon: 'odnoklassniki', prefix: 'fab fa-', url: `https://connect.ok.ru/offer?url=${currentUrl}&title=${title}` },
        { name: 'Tiktok', icon: 'tiktok', prefix: 'fab fa-', url: `https://www.tiktok.com/?${currentUrl}` },
        { name: 'Viber', icon: 'viber', prefix: 'fab fa-', url: `https://invite.viber.com/?g2=445da6az1s345z78-dazcczb2542zv51a-e0vc5fva17480im9` },
        { name: 'Spotify', icon: 'spotify', prefix: 'fab fa-', url: `https://open.spotify.com/uri?${currentUrl}` },
        { name: 'Instagram', icon: 'instagram', prefix: 'fab fa-', url: `https://www.instagram.com/?url=${currentUrl}&title=${title}` },
        { name: 'YouTube', icon: 'youtube', prefix: 'fab fa-', url: `https://www.youtube.com/share?url=${currentUrl}` },
        { name: 'RuTube', icon: 'play-circle', prefix: 'fas fa-', url: `https://rutube.ru/share/video/?url=${currentUrl}` },
        { name: 'Мой Мир', icon: 'globe', prefix: 'fas fa-', url: `https://connect.mail.ru/share?url=${currentUrl}&title=${title}` }
    ];

    container.innerHTML = socialNetworks.map(network =>
        `<a href="${network.url}" target="_blank" rel="noopener noreferrer" 
          class="social-button" title="Поделиться на ${network.name}">
          <i aria-hidden="true" class="${network.prefix}${network.icon}"></i></a>`
    ).join('');
});
