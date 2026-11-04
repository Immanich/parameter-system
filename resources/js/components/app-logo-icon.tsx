import { SVGAttributes } from 'react';

/** ParaMeter mark: a fare-meter gauge. Draws in `currentColor`. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" {...props}>
            <path d="M7 29a14 14 0 1 1 26 0" fill="none" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round" />
            <path d="M12.4 15.8l1.8 1.6M20 11.2v2.4M27.6 15.8l-1.8 1.6" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
            <path d="M20 23.4l6.6-6.9" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
            <circle cx="20" cy="23.4" r="3.2" fill="currentColor" stroke="none" />
        </svg>
    );
}
