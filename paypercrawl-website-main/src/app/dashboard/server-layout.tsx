import { redirect } from 'next/navigation';
import { cookies } from 'next/headers';

export default async function DashboardServerLayout({
  children,
  searchParams,
}: {
  children: React.ReactNode;
  searchParams: { token?: string };
}) {
  // Server-side token check
  const cookieStore = await cookies();
  const cookieToken = cookieStore.get('invite_token')?.value;
  const urlToken = searchParams.token;

  console.log('Server-side check - URL token:', !!urlToken, 'Cookie token:', !!cookieToken);

  // If no token found, redirect immediately
  if (!urlToken && !cookieToken) {
    console.log('Server-side redirect - no token found');
    redirect('/waitlist');
  }

  return <>{children}</>;
}
