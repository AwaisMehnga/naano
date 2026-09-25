import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!reduce) {
    const hero = document.querySelector('[data-hero]');
    const heroCopy = hero?.querySelector(':scope > div:first-child > div');
    const heroCards = document.querySelectorAll('[data-hero-card]');

    if (heroCopy) {
        gsap.from(heroCopy.children, {
            opacity: 0,
            y: 18,
            duration: 0.55,
            stagger: 0.08,
            ease: 'power3.out',
        });
    }

    if (heroCards.length) {
        gsap.from(heroCards, {
            opacity: 0,
            y: 36,
            duration: 0.65,
            stagger: 0.12,
            delay: 0.12,
            ease: 'power3.out',
        });
    }

    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%' },
            opacity: 0,
            y: 20,
            duration: 0.5,
            ease: 'power3.out',
        });
    });
}
