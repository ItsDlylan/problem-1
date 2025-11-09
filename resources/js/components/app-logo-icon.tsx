import { SVGAttributes } from 'react';

/**
 * Doctor logo icon with lab coat and stethoscope
 * Features a friendly doctor figure wearing a white lab coat
 * Includes a detailed stethoscope around the neck with chest piece
 * Designed to be simple and recognizable at small sizes
 * 
 * Design approach:
 * - Head and body use main color (darker)
 * - Lab coat uses full opacity to appear bright/white
 * - Stethoscope uses main color for visibility
 * - Creates clear visual distinction between body and lab coat
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 42" xmlns="http://www.w3.org/2000/svg">
            {/* Doctor's body/shirt - base layer under the lab coat, much darker */}
            {/* This creates strong contrast so the lab coat appears bright white */}
            <rect x="15" y="20" width="10" height="16" rx="1.5" fill="currentColor" opacity="0.25" />
            
            {/* Lab coat - main white coat that goes over the body */}
            {/* Uses full opacity (100%) to appear bright white and distinct from darker body */}
            <path
                d="M 13 20 L 13 36 L 27 36 L 27 20 L 25 20 L 25 22 L 15 22 L 15 20 Z"
                fill="currentColor"
            />
            
            {/* Lab coat collar - V-neck style, bright white */}
            <path
                d="M 20 20 L 15 22 L 20 24 L 25 22 Z"
                fill="currentColor"
            />
            
            {/* Lab coat buttons - small circles down the front for detail */}
            {/* Darker to show as buttons/accents on the bright white coat */}
            <circle cx="20" cy="26" r="0.8" fill="currentColor" opacity="0.4" />
            <circle cx="20" cy="29" r="0.8" fill="currentColor" opacity="0.4" />
            <circle cx="20" cy="32" r="0.8" fill="currentColor" opacity="0.4" />
            
            {/* Doctor's head - circular and friendly */}
            {/* Slightly darker than lab coat but lighter than body to show it's part of the person */}
            <circle cx="20" cy="12" r="7" fill="currentColor" opacity="0.6" />
            
            {/* Stethoscope - hangs around the neck, tubes go down naturally */}
            {/* The stethoscope wraps around the neck area and hangs down */}
            {/* Left side - curves around neck and hangs down */}
            <path
                d="M 20 19 Q 16 19 14 20 Q 12 22 12 26 Q 12 30 13 32"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                opacity="0.8"
            />
            
            {/* Right side - curves around neck and hangs down */}
            <path
                d="M 20 19 Q 24 19 26 20 Q 28 22 28 26 Q 28 30 27 32"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                opacity="0.8"
            />
            
            {/* Stethoscope chest piece - hangs down from the neck */}
            {/* This is the part that doctors use to listen to patients */}
            <circle cx="20" cy="28" r="2.5" fill="none" stroke="currentColor" strokeWidth="2" opacity="0.8" />
            <circle cx="20" cy="28" r="1.2" fill="currentColor" opacity="0.8" />
            
            {/* Stethoscope earpieces - positioned at the sides of the head/neck */}
            {/* These are where the doctor puts the earpieces in their ears */}
            <circle cx="14" cy="20" r="1.8" fill="currentColor" opacity="0.8" />
            <circle cx="26" cy="20" r="1.8" fill="currentColor" opacity="0.8" />
            
            {/* Doctor's face - simple eyes for cuteness */}
            {/* Eyes use lighter opacity to show as white on dark head */}
            <circle cx="17.5" cy="11" r="1" fill="currentColor" opacity="0.15" />
            <circle cx="22.5" cy="11" r="1" fill="currentColor" opacity="0.15" />
            
            {/* Simple smile */}
            <path
                d="M 17 14 Q 20 16 23 14"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                opacity="0.2"
            />
        </svg>
    );
}
