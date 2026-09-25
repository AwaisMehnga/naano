import { createRoot, type Root } from 'react-dom/client';
import ComponentsGalleryPage from '@/pages/components';

declare global {
    interface Window {
        __naanoGalleryRoot?: Root;
    }
}

const el = document.getElementById('app');

if (el) {
    const root = window.__naanoGalleryRoot ?? createRoot(el);
    window.__naanoGalleryRoot = root;
    root.render(<ComponentsGalleryPage />);
}
