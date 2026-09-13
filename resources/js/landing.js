import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!reduce) {
    const hero = document.querySelector('[data-hero]');
    const heroBits = hero?.querySelectorAll(':scope > * > *');

    gsap.from(heroBits ?? '[data-hero] > *', {
        opacity: 0,
        y: 16,
        duration: 0.5,
        stagger: 0.08,
        ease: 'power3.out',
    });

    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%' },
            opacity: 0,
            y: 16,
            duration: 0.45,
            ease: 'power3.out',
        });
    });
}
