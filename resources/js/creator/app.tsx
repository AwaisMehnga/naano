import { createRoot, type Root } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppRouter from './app-router';

declare global {
    interface Window {
        __naanoCreatorRoot?: Root;
    }
}

function App() {
    if (!window.Naano) {
        throw new Error('Missing window.Naano');
    }

    return (
        <BrowserRouter basename="/creator">
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

const root = window.__naanoCreatorRoot ?? createRoot(el);
window.__naanoCreatorRoot = root;
root.render(<App />);
