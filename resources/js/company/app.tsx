import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppRouter from './app-router';

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

const root = document.getElementById('app');

if (!root) {
    throw new Error('Missing #app');
}

createRoot(root).render(<App />);
