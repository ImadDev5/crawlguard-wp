"use client";

import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    // Check for token immediately when layout loads
    const urlToken = searchParams.get('token');
    const cookieToken = typeof window !== 'undefined' 
      ? document.cookie
          .split(';')
          .find(row => row.trim().startsWith('invite_token='))
          ?.split('=')[1]
      : null;

    console.log('Dashboard layout check - URL token:', !!urlToken, 'Cookie token:', !!cookieToken);

    if (!urlToken && !cookieToken) {
      console.log('Layout redirect - no token found');
      router.replace('/waitlist');
      return;
    }
  }, [searchParams, router]);

  return <>{children}</>;
}
