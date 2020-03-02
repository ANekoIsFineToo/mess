export function debounce(wait, fn) {
    let timeout;

    return function() {
        const args = arguments;
        const later = () => {
            timeout = undefined;
            fn.apply(this, args);
        };

        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
