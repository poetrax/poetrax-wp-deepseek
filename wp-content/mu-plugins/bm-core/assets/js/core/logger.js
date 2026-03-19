/**
 * Logger Module
 */

const Logger = {
    debug: (...args) => {
        if (window.BM_DEBUG) {
            console.debug('[BM]', ...args);
        }
    },
    
    info: (...args) => {
        console.info('[BM]', ...args);
    },
    
    warn: (...args) => {
        console.warn('[BM]', ...args);
    },
    
    error: (error, context = {}) => {
        console.error('[BM]', error, context);
    }
};

window.Logger = Logger;
