import { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/**
 * Penguin doctor logo icon component
 * Uses the penguin_transparent.png image instead of SVG
 * Accepts standard img attributes for flexible sizing and styling
 * Note: SVG-specific classes like "fill-current" are ignored for img tags
 */
export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    const { className, ...restProps } = props;
    return (
        <img
            {...restProps}
            src="/penguin_transparent.png"
            alt="MedAI Penguin Doctor Logo"
            className={cn("object-contain", className)}
        />
    );
}
