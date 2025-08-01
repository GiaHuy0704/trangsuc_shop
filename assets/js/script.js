// Fade-in animation khi cuộn xuống
document.addEventListener("DOMContentLoaded", function () {
    const faders = document.querySelectorAll(".promotion-item, .product-item");

    const appearOptions = {
        threshold: 0.3,
        rootMargin: "0px 0px -50px 0px"
    };

    const appearOnScroll = new IntersectionObserver(function(entries, observer) {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add("fade-in");
            observer.unobserve(entry.target);
        });
    }, appearOptions);

    faders.forEach(fader => {
        fader.classList.add("fade-init");
        appearOnScroll.observe(fader);
    });
});
