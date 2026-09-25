import { createRoot } from 'react-dom/client';
import ComponentsGalleryPage from '@/pages/components';

const el = document.getElementById('app');

if (el) {
    createRoot(el).render(<ComponentsGalleryPage />);
}
