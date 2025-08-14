"use client";

import { useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Shield, Loader2, AlertCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";

export default function AccessContent() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    const token = searchParams.get("token");

    if (token) {
      router.replace(`/dashboard?token=${token}`);
    } else {
      const id = setTimeout(() => {
        router.replace("/waitlist");
      }, 3000);
      return () => clearTimeout(id);
    }
  }, [searchParams, router]);

  const token = searchParams.get("token");

  if (token) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <Card className="w-[400px]">
          <CardHeader className="text-center">
            <div className="flex items-center justify-center space-x-2 mb-4">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-2xl font-bold">PayPerCrawl</span>
            </div>
            <CardTitle>Redirecting to Dashboard</CardTitle>
            <CardDescription>
              Please wait while we redirect you to your beta dashboard...
            </CardDescription>
          </CardHeader>
          <CardContent className="flex justify-center">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background flex items-center justify-center">
      <Card className="w-[400px]">
        <CardHeader className="text-center">
          <div className="flex items-center justify-center space-x-2 mb-4">
            <AlertCircle className="h-8 w-8 text-destructive" />
            <span className="text-2xl font-bold">PayPerCrawl</span>
          </div>
          <CardTitle>Access Denied</CardTitle>
          <CardDescription>
            No valid invitation token found. You need an invitation to access
            the dashboard.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-muted-foreground text-center">
            Redirecting to waitlist in a few seconds...
          </p>
          <div className="flex justify-center">
            <Link href="/waitlist">
              <Button>Join Waitlist</Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
