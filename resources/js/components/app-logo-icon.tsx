import { SVGAttributes } from 'react';

/**
 * Penguin doctor logo icon with lab coat and stethoscope
 * Features a classic penguin shape with proper proportions
 * Includes a detailed stethoscope around the neck with chest piece
 * Designed to be simple and recognizable at small sizes
 * 
 * Classic penguin features:
 * - Round head with black top
 * - White face/chest area (the classic penguin "bib")
 * - Black back and sides
 * - White belly
 * - Orange beak
 * - Flippers on the sides
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 42" xmlns="http://www.w3.org/2000/svg">
            {/* Penguin's black back - classic penguin shape, rounded oval */}
            <ellipse cx="20" cy="24" rx="7" ry="10" fill="currentColor" opacity="0.8" />
            
            {/* Penguin's white belly/front - distinctive penguin feature */}
            {/* This creates the classic black and white contrast */}
            <ellipse cx="20" cy="25" rx="5" ry="8" fill="currentColor" opacity="0.2" />
            
            {/* Penguin's head - black, round */}
            <circle cx="20" cy="10" r="6" fill="currentColor" opacity="0.8" />
            
            {/* Penguin's white face patch - classic penguin "bib" pattern */}
            {/* This is the distinctive white area that goes from face down to chest */}
            <path
                d="M 20 8 Q 20 12 20 16 Q 18 18 16 20 Q 14 22 15 24 Q 16 26 18 27 Q 20 28 22 27 Q 24 26 25 24 Q 26 22 24 20 Q 22 18 20 16 Q 20 12 20 8 Z"
                fill="currentColor"
                opacity="0.2"
            />
            
            {/* Penguin's left flipper - distinctive penguin feature, on the side */}
            <ellipse cx="11" cy="23" rx="2.5" ry="5" fill="currentColor" opacity="0.7" transform="rotate(-20 11 23)" />
            
            {/* Penguin's right flipper - on the other side */}
            <ellipse cx="29" cy="23" rx="2.5" ry="5" fill="currentColor" opacity="0.7" transform="rotate(20 29 23)" />
            
            {/* Lab coat - white coat that goes over the penguin */}
            {/* Positioned to show the penguin's distinctive features */}
            <path
                d="M 11 21 L 11 36 L 29 36 L 29 21 L 27 21 L 27 23 L 13 23 L 13 21 Z"
                fill="currentColor"
            />
            
            {/* Lab coat collar - V-neck style */}
            <path
                d="M 20 21 L 13 23 L 20 25 L 27 23 Z"
                fill="currentColor"
            />
            
            {/* Lab coat buttons */}
            <circle cx="20" cy="27" r="0.7" fill="currentColor" opacity="0.4" />
            <circle cx="20" cy="30" r="0.7" fill="currentColor" opacity="0.4" />
            <circle cx="20" cy="33" r="0.7" fill="currentColor" opacity="0.4" />
            
            {/* Penguin's beak - orange/yellow triangle */}
            <path
                d="M 20 12 L 18 15 L 20 16 L 22 15 Z"
                fill="currentColor"
                opacity="0.8"
            />
            
            {/* Stethoscope - hangs around the neck */}
            {/* Left side */}
            <path
                d="M 20 21 Q 16 21 14 22 Q 12 24 12 28 Q 12 32 13 34"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                opacity="0.8"
            />
            
            {/* Right side */}
            <path
                d="M 20 21 Q 24 21 26 22 Q 28 24 28 28 Q 28 32 27 34"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                opacity="0.8"
            />
            
            {/* Stethoscope chest piece */}
            <circle cx="20" cy="30" r="2.5" fill="none" stroke="currentColor" strokeWidth="2" opacity="0.8" />
            <circle cx="20" cy="30" r="1.2" fill="currentColor" opacity="0.8" />
            
            {/* Stethoscope earpieces */}
            <circle cx="14" cy="22" r="1.6" fill="currentColor" opacity="0.8" />
            <circle cx="26" cy="22" r="1.6" fill="currentColor" opacity="0.8" />
            
            {/* Penguin's eyes - on the black head */}
            <circle cx="17.5" cy="9" r="1" fill="currentColor" opacity="0.12" />
            <circle cx="22.5" cy="9" r="1" fill="currentColor" opacity="0.12" />
        </svg>
    );
}
