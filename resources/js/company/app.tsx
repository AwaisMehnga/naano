import { createRoot, type Root } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppRouter from './app-router';

declare global {
    interface Window {
        __naanoCompanyRoot?: Root;
    }
}

function App() {
    if (!window.Naano) {
        throw new Error('Missing window.Naano');
    }

    return (
        <BrowserRouter basename="/company">
            <TooltipProvider delayDuration={0}>
                <AppRouter />
                <Toaster />
            </TooltipProvider>
        </BrowserRouter>
    );
}

const el = document.getElementById('app');

if (!el) {
    throw new Error('Missing #app');
}

const root = window.__naanoCompanyRoot ?? createRoot(el);
window.__naanoCompanyRoot = root;
root.render(<App />);
