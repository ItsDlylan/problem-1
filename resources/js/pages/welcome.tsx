import { type SharedData } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { useEffect, useRef } from "react";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { ChevronDownIcon, Users, Building2, Calendar, Award, Shield, Sparkles, Clock } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

// Register ScrollTrigger plugin - this enables scroll-based animations
gsap.registerPlugin(ScrollTrigger);

export default function Welcome() {
  const { auth } = usePage<SharedData>().props;
  const heroRef = useRef<HTMLDivElement>(null);
  const titleRef = useRef<HTMLHeadingElement>(null);
  const subtitleRef = useRef<HTMLParagraphElement>(null);
  const ctaRef = useRef<HTMLDivElement>(null);
  const featuresRef = useRef<HTMLDivElement>(null);
  const featureCardsRef = useRef<HTMLDivElement>(null);
  const statsRef = useRef<HTMLDivElement>(null);
  const doctorImageRef = useRef<HTMLDivElement>(null);
  const hospitalsRef = useRef<HTMLDivElement>(null);
  const patientsRef = useRef<HTMLDivElement>(null);
  const appointmentsRef = useRef<HTMLDivElement>(null);

  // Hero section animations on page load
  useEffect(() => {
    const ctx = gsap.context(() => {
      // Animate hero elements with warm, welcoming entrance
      const tl = gsap.timeline({ defaults: { ease: "power3.out" } });

      // Title animation - fade in and slide up with a gentle bounce
      if (titleRef.current) {
        tl.from(titleRef.current, {
          opacity: 0,
          y: 80,
          duration: 1.4,
          ease: "back.out(1.4)",
        });
      }

      // Subtitle animation - fade in with slight delay
      if (subtitleRef.current) {
        tl.from(
          subtitleRef.current,
          {
            opacity: 0,
            y: 40,
            duration: 1,
          },
          "-=0.8"
        );
      }

      // Doctor image animation - scale and fade in
      if (doctorImageRef.current) {
        tl.from(
          doctorImageRef.current,
          {
            opacity: 0,
            scale: 0.8,
            rotation: -5,
            duration: 1.2,
            ease: "elastic.out(1, 0.5)",
          },
          "-=0.6"
        );
      }

      // CTA buttons animation - stagger fade in with bounce
      if (ctaRef.current && ctaRef.current.children.length > 0) {
        tl.from(
          Array.from(ctaRef.current.children),
          {
            opacity: 0,
            y: 30,
            scale: 0.9,
            duration: 0.8,
            stagger: 0.2,
            ease: "back.out(1.2)",
          },
          "-=0.5"
        );
      }

      // Continuous floating animation for hero section
      if (heroRef.current) {
        gsap.to(heroRef.current, {
          y: -15,
          duration: 4,
          ease: "sine.inOut",
          yoyo: true,
          repeat: -1,
        });
      }

      // Rotating gradient background animation
      gsap.to(".gradient-orb-1", {
        rotation: 360,
        duration: 20,
        ease: "none",
        repeat: -1,
      });

      gsap.to(".gradient-orb-2", {
        rotation: -360,
        duration: 25,
        ease: "none",
        repeat: -1,
      });

      // Pulsing animation for decorative elements
      gsap.to(".pulse-element", {
        scale: 1.2,
        opacity: 0.6,
        duration: 2,
        ease: "sine.inOut",
        yoyo: true,
        repeat: -1,
      });
    });

    return () => ctx.revert();
  }, []);

  // ScrollTrigger animations for features section
  useEffect(() => {
    const ctx = gsap.context(() => {
      // Animate feature cards on scroll with rotation
      const cards = featureCardsRef.current?.children || [];
      
      // Set initial state to ensure visibility
      gsap.set(cards, { opacity: 1, y: 0, rotation: 0 });
      
      gsap.from(cards, {
        scrollTrigger: {
          trigger: featuresRef.current,
          start: "top 85%",
          toggleActions: "play none none reverse",
        },
        opacity: 0,
        y: 80,
        rotation: -5,
        duration: 1,
        stagger: 0.15,
        ease: "back.out(1.2)",
      });

      // Parallax effect for feature section title
      const featureTitle = featuresRef.current?.querySelector("h2");
      if (featureTitle) {
        gsap.to(featureTitle, {
          scrollTrigger: {
            trigger: featuresRef.current,
            start: "top 80%",
            end: "bottom 20%",
            scrub: 1,
          },
          y: -40,
          opacity: 0.9,
          ease: "none",
        });
      }
    });

    return () => ctx.revert();
  }, []);

  // Statistics counter animation
  useEffect(() => {
    const ctx = gsap.context(() => {
      const stats = statsRef.current;
      if (!stats) return;

      // Animate stat cards on scroll
      const statCards = stats.querySelectorAll(".stat-card");
      gsap.from(statCards, {
        scrollTrigger: {
          trigger: stats,
          start: "top 80%",
          toggleActions: "play none none reverse",
        },
        opacity: 0,
        y: 50,
        scale: 0.9,
        duration: 0.8,
        stagger: 0.2,
        ease: "back.out(1.2)",
      });

      // Animate counters using GSAP's text plugin approach
      if (hospitalsRef.current) {
        gsap.to(
          { value: 0 },
          {
            value: 250,
            duration: 2,
            ease: "power2.out",
            scrollTrigger: {
              trigger: hospitalsRef.current,
              start: "top 80%",
              toggleActions: "play none none reverse",
            },
            onUpdate: function () {
              if (hospitalsRef.current) {
                hospitalsRef.current.textContent =
                  Math.floor(this.targets()[0].value).toLocaleString() + "+";
              }
            },
          }
        );
      }

      if (patientsRef.current) {
        gsap.to(
          { value: 0 },
          {
            value: 50000,
            duration: 2.5,
            ease: "power2.out",
            scrollTrigger: {
              trigger: patientsRef.current,
              start: "top 80%",
              toggleActions: "play none none reverse",
            },
            onUpdate: function () {
              if (patientsRef.current) {
                patientsRef.current.textContent =
                  Math.floor(this.targets()[0].value).toLocaleString() + "+";
              }
            },
          }
        );
      }

      if (appointmentsRef.current) {
        gsap.to(
          { value: 0 },
          {
            value: 1000000,
            duration: 3,
            ease: "power2.out",
            scrollTrigger: {
              trigger: appointmentsRef.current,
              start: "top 80%",
              toggleActions: "play none none reverse",
            },
            onUpdate: function () {
              if (appointmentsRef.current) {
                appointmentsRef.current.textContent =
                  Math.floor(this.targets()[0].value).toLocaleString() + "+";
              }
            },
          }
        );
      }
    });

    return () => ctx.revert();
  }, []);

  return (
    <>
      <Head title="Welcome">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
          href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
          rel="stylesheet"
        />
      </Head>
      <div className="min-h-screen bg-gradient-to-br from-[#FFF8F0] via-[#FDFDFC] to-[#FFF5E6] text-[#1b1b18] dark:from-[#0a0a0a] dark:via-[#161615] dark:to-[#1a1a18]">
        {/* Navigation */}
        <header className="fixed top-0 z-50 w-full border-b border-[#19140015] bg-white/80 backdrop-blur-sm dark:border-[#3E3E3A] dark:bg-[#161615]/80">
          <nav className="container mx-auto flex items-center justify-between px-6 py-4 lg:px-8">
            <div className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
              MedAI
            </div>
            {!auth.user && (
              <div className="flex items-center gap-4">
                {/* Login Dropdown */}
                <DropdownMenu>
                  <DropdownMenuTrigger className="inline-flex items-center gap-1 rounded-lg border border-transparent px-5 py-2 text-sm font-medium text-[#1b1b18] transition-all hover:border-[#19140035] hover:bg-[#FDFDFC] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A] dark:hover:bg-[#161615]">
                    Log in
                    <ChevronDownIcon className="h-4 w-4" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="min-w-[180px]">
                    <DropdownMenuItem asChild>
                      <Link href="/patient/login" className="cursor-pointer">
                        Login as Patient
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/facility/login" className="cursor-pointer">
                        Login as Facility
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>

                {/* Register Dropdown */}
                <DropdownMenu>
                  <DropdownMenuTrigger className="inline-flex items-center gap-1 rounded-lg border border-[#19140035] bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white transition-all hover:bg-black dark:border-[#3E3E3A] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-white">
                    Register
                    <ChevronDownIcon className="h-4 w-4" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="min-w-[180px]">
                    <DropdownMenuItem asChild>
                      <Link href="/patient/register" className="cursor-pointer">
                        Register as Patient
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/facility/register" className="cursor-pointer">
                        Register as Facility
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            )}
          </nav>
        </header>

        {/* Hero Section */}
        <section
          ref={heroRef}
          className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 pt-24 pb-12 lg:px-8 lg:pt-32"
        >
          {/* Animated background orbs */}
          <div className="gradient-orb-1 absolute left-10 top-1/4 h-64 w-64 rounded-full bg-gradient-to-br from-[#F53003]/30 to-transparent blur-3xl dark:from-[#FF4433]/30" />
          <div className="gradient-orb-2 absolute right-10 top-1/3 h-80 w-80 rounded-full bg-gradient-to-br from-[#FF750F]/30 to-transparent blur-3xl dark:from-[#FF9500]/30" />
          <div className="pulse-element absolute left-1/2 top-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-br from-[#F53003]/10 to-[#FF750F]/10 blur-3xl dark:from-[#FF4433]/10 dark:to-[#FF9500]/10" />

          <div className="container relative z-10 mx-auto max-w-7xl">
            <div className="grid items-center gap-12 lg:grid-cols-2">
              {/* Left side - Text content */}
              <div className="text-center lg:text-left">
                <h1
                  ref={titleRef}
                  className="mb-6 text-5xl font-bold leading-tight text-[#1b1b18] lg:text-7xl dark:text-[#EDEDEC]"
                >
                  Welcome to Your
                  <span className="block bg-gradient-to-r from-[#F53003] to-[#FF750F] bg-clip-text text-transparent dark:from-[#FF4433] dark:to-[#FF9500]">
                    {" "}
                    Health Journey
                  </span>
                </h1>
                <p
                  ref={subtitleRef}
                  className="mb-10 text-xl leading-relaxed text-[#706f6c] lg:text-2xl dark:text-[#A1A09A]"
                >
                  Schedule appointments effortlessly and manage your calendar with
                  ease. Your health, simplified.
                </p>
                <div ref={ctaRef} className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center lg:justify-start">
                  {!auth.user ? (
                    <>
                      <Link
                        href="/patient/register"
                        className="group inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-[#F53003] to-[#FF750F] px-8 py-4 text-lg font-semibold text-white shadow-lg transition-all hover:scale-110 hover:shadow-2xl dark:from-[#FF4433] dark:to-[#FF9500]"
                      >
                        Get Started
                        <svg
                          className="h-5 w-5 transition-transform group-hover:translate-x-1"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                          />
                        </svg>
                      </Link>
                      <Link
                        href="/patient/login"
                        className="inline-flex items-center justify-center rounded-lg border-2 border-[#19140035] bg-white px-8 py-4 text-lg font-semibold text-[#1b1b18] transition-all hover:border-[#1915014a] hover:bg-[#FDFDFC] hover:scale-105 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                      >
                        View Calendar
                      </Link>
                    </>
                  ) : (
                    <Link
                      href="/patient/dashboard"
                      className="group inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-[#F53003] to-[#FF750F] px-8 py-4 text-lg font-semibold text-white shadow-lg transition-all hover:scale-110 hover:shadow-2xl dark:from-[#FF4433] dark:to-[#FF9500]"
                    >
                      Go to Dashboard
                      <svg
                        className="h-5 w-5 transition-transform group-hover:translate-x-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                      </svg>
                    </Link>
                  )}
                </div>
              </div>

              {/* Right side - Doctor illustration */}
              <div ref={doctorImageRef} className="relative hidden lg:block">
                <div className="relative">
                  {/* Doctor SVG Illustration */}
                  <svg
                    viewBox="0 0 400 500"
                    className="h-full w-full max-w-md"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    {/* Doctor figure */}
                    <g>
                      {/* Head */}
                      <circle
                        cx="200"
                        cy="120"
                        r="50"
                        fill="#FFE5D9"
                        className="dark:fill-[#3E3E3A]"
                      />
                      {/* Body */}
                      <rect
                        x="150"
                        y="170"
                        width="100"
                        height="200"
                        rx="10"
                        fill="#4A90E2"
                        className="dark:fill-[#4A90E2]"
                      />
                      {/* Stethoscope */}
                      <path
                        d="M180 200 Q200 190 220 200"
                        stroke="#1b1b18"
                        strokeWidth="3"
                        fill="none"
                        className="dark:stroke-[#EDEDEC]"
                      />
                      <circle
                        cx="200"
                        cy="220"
                        r="15"
                        fill="none"
                        stroke="#1b1b18"
                        strokeWidth="3"
                        className="dark:stroke-[#EDEDEC]"
                      />
                      {/* Arms */}
                      <rect
                        x="130"
                        y="200"
                        width="30"
                        height="100"
                        rx="15"
                        fill="#FFE5D9"
                        className="dark:fill-[#3E3E3A]"
                      />
                      <rect
                        x="240"
                        y="200"
                        width="30"
                        height="100"
                        rx="15"
                        fill="#FFE5D9"
                        className="dark:fill-[#3E3E3A]"
                      />
                      {/* Legs */}
                      <rect
                        x="160"
                        y="370"
                        width="25"
                        height="80"
                        rx="12"
                        fill="#2C3E50"
                        className="dark:fill-[#2C3E50]"
                      />
                      <rect
                        x="215"
                        y="370"
                        width="25"
                        height="80"
                        rx="12"
                        fill="#2C3E50"
                        className="dark:fill-[#2C3E50]"
                      />
                      {/* Medical cross badge */}
                      <circle
                        cx="200"
                        cy="250"
                        r="20"
                        fill="#F53003"
                        className="dark:fill-[#FF4433]"
                      />
                      <path
                        d="M200 240 L200 260 M190 250 L210 250"
                        stroke="white"
                        strokeWidth="3"
                        strokeLinecap="round"
                      />
                    </g>
                  </svg>

                  {/* Floating elements around doctor */}
                  <div className="absolute -left-10 top-20 animate-bounce">
                    <div className="rounded-full bg-gradient-to-br from-[#F53003] to-[#FF750F] p-3 shadow-lg dark:from-[#FF4433] dark:to-[#FF9500]">
                      <Calendar className="h-6 w-6 text-white" />
                    </div>
                  </div>
                  <div className="absolute -right-10 top-40 animate-bounce" style={{ animationDelay: "0.5s" }}>
                    <div className="rounded-full bg-gradient-to-br from-[#F53003] to-[#FF750F] p-3 shadow-lg dark:from-[#FF4433] dark:to-[#FF9500]">
                      <Users className="h-6 w-6 text-white" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Statistics Section */}
        <section
          ref={statsRef}
          className="container mx-auto px-6 py-16 lg:px-8"
        >
          <div className="grid gap-8 md:grid-cols-3">
            {/* Stat Card 1 */}
            <div className="stat-card rounded-2xl border border-[#e3e3e0] bg-white p-8 text-center shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615]">
              <div className="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <Building2 className="h-8 w-8 text-white" />
              </div>
              <div
                ref={hospitalsRef}
                className="mb-2 text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]"
              >
                0+
              </div>
              <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                Hospitals & Facilities
              </p>
            </div>

            {/* Stat Card 2 */}
            <div className="stat-card rounded-2xl border border-[#e3e3e0] bg-white p-8 text-center shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615]">
              <div className="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <Users className="h-8 w-8 text-white" />
              </div>
              <div
                ref={patientsRef}
                className="mb-2 text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]"
              >
                0+
              </div>
              <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                Active Patients
              </p>
            </div>

            {/* Stat Card 3 */}
            <div className="stat-card rounded-2xl border border-[#e3e3e0] bg-white p-8 text-center shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615]">
              <div className="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <Award className="h-8 w-8 text-white" />
              </div>
              <div
                ref={appointmentsRef}
                className="mb-2 text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]"
              >
                0+
              </div>
              <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                Appointments Scheduled
              </p>
            </div>
          </div>
        </section>

        {/* Features Section */}
        <section
          ref={featuresRef}
          className="relative container mx-auto px-6 py-24 lg:px-8"
        >
          <h2 className="mb-16 text-center text-4xl font-bold text-[#1b1b18] lg:text-5xl dark:text-[#EDEDEC]">
            Everything You Need
          </h2>
          <div
            ref={featureCardsRef}
            className="relative z-10 grid gap-8 md:grid-cols-2 lg:grid-cols-3"
          >
            {/* Feature Card 1 */}
            <div
              className="feature-card group relative rounded-2xl border-2 border-[#e3e3e0] bg-white p-8 shadow-lg transition-all hover:shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
              onMouseEnter={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1.05,
                  y: -8,
                  rotation: 1,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
              onMouseLeave={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1,
                  y: 0,
                  rotation: 0,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
            >
              <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <svg
                  className="h-6 w-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
              </div>
              <h3 className="mb-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Easy Scheduling
              </h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                Book appointments in just a few clicks. Find available slots
                that fit your schedule perfectly.
              </p>
            </div>

            {/* Feature Card 2 */}
            <div
              className="feature-card group relative rounded-2xl border-2 border-[#e3e3e0] bg-white p-8 shadow-lg transition-all hover:shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
              onMouseEnter={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1.05,
                  y: -8,
                  rotation: -1,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
              onMouseLeave={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1,
                  y: 0,
                  rotation: 0,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
            >
              <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <svg
                  className="h-6 w-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                  />
                </svg>
              </div>
              <h3 className="mb-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Calendar Management
              </h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                View all your appointments in one place. Never miss an
                important visit again.
              </p>
            </div>

            {/* Feature Card 3 */}
            <div
              className="feature-card group relative rounded-2xl border-2 border-[#e3e3e0] bg-white p-8 shadow-lg transition-all hover:shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
              onMouseEnter={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1.05,
                  y: -8,
                  rotation: 1,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
              onMouseLeave={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1,
                  y: 0,
                  rotation: 0,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
            >
              <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <svg
                  className="h-6 w-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <h3 className="mb-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Reminders & Notifications
              </h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                Get timely reminders so you're always prepared for your
                appointments.
              </p>
            </div>

            {/* Feature Card 4 - AI-Powered Recommendations */}
            <div
              className="feature-card group relative rounded-2xl border-2 border-[#e3e3e0] bg-white p-8 shadow-lg transition-all hover:shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
              onMouseEnter={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1.05,
                  y: -8,
                  rotation: -1,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
              onMouseLeave={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1,
                  y: 0,
                  rotation: 0,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
            >
              <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <Sparkles className="h-6 w-6 text-white" />
              </div>
              <h3 className="mb-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                AI-Powered Recommendations
              </h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                Get personalized doctor and appointment suggestions based on your
                health history and preferences.
              </p>
            </div>

            {/* Feature Card 5 - Secure & Private */}
            <div
              className="feature-card group relative rounded-2xl border-2 border-[#e3e3e0] bg-white p-8 shadow-lg transition-all hover:shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
              onMouseEnter={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1.05,
                  y: -8,
                  rotation: 1,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
              onMouseLeave={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1,
                  y: 0,
                  rotation: 0,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
            >
              <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <Shield className="h-6 w-6 text-white" />
              </div>
              <h3 className="mb-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Secure & Private
              </h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                Your health data is protected with bank-level encryption. HIPAA
                compliant and fully secure.
              </p>
            </div>

            {/* Feature Card 6 - Real-Time Availability */}
            <div
              className="feature-card group relative rounded-2xl border-2 border-[#e3e3e0] bg-white p-8 shadow-lg transition-all hover:shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
              onMouseEnter={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1.05,
                  y: -8,
                  rotation: -1,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
              onMouseLeave={(e) => {
                gsap.to(e.currentTarget, {
                  scale: 1,
                  y: 0,
                  rotation: 0,
                  duration: 0.4,
                  ease: "power2.out",
                });
              }}
            >
              <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-[#F53003] to-[#FF750F] dark:from-[#FF4433] dark:to-[#FF9500]">
                <Clock className="h-6 w-6 text-white" />
              </div>
              <h3 className="mb-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Real-Time Availability
              </h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                See live appointment slots updated in real-time. Book instantly
                when availability opens up.
              </p>
            </div>
          </div>
        </section>

        {/* CTA Section */}
        <section className="container mx-auto px-6 py-24 lg:px-8">
          <div className="relative overflow-hidden rounded-3xl border border-[#e3e3e0] bg-gradient-to-br from-[#FFF8F0] to-[#FFF5E6] p-12 text-center shadow-xl dark:border-[#3E3E3A] dark:from-[#161615] dark:to-[#1a1a18]">
            <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-gradient-to-br from-[#F53003]/30 to-transparent blur-3xl dark:from-[#FF4433]/30" />
            <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-gradient-to-br from-[#FF750F]/30 to-transparent blur-3xl dark:from-[#FF9500]/30" />
            <div className="relative z-10">
              <h2 className="mb-4 text-3xl font-bold text-[#1b1b18] lg:text-4xl dark:text-[#EDEDEC]">
                Ready to Get Started?
              </h2>
              <p className="mb-8 text-lg text-[#706f6c] dark:text-[#A1A09A]">
                Join thousands of users managing their health appointments with
                ease.
              </p>
              {!auth.user && (
                <Link
                  href="/patient/register"
                  className="group inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-[#F53003] to-[#FF750F] px-8 py-4 text-lg font-semibold text-white shadow-lg transition-all hover:scale-110 hover:shadow-2xl dark:from-[#FF4433] dark:to-[#FF9500]"
                >
                  Start Your Journey Today
                  <svg
                    className="h-5 w-5 transition-transform group-hover:translate-x-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M13 7l5 5m0 0l-5 5m5-5H6"
                    />
                  </svg>
                </Link>
              )}
            </div>
          </div>
        </section>

        {/* Footer */}
        <footer className="border-t border-[#e3e3e0] bg-white py-8 dark:border-[#3E3E3A] dark:bg-[#161615]">
          <div className="container mx-auto px-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A] lg:px-8">
            <p>© {new Date().getFullYear()} MedAI. All rights reserved.</p>
          </div>
        </footer>
      </div>
    </>
  );
}
