"use client";

import { useState } from "react";
import { useAuth } from "@/contexts/AuthContext";
import { useRouter } from "next/navigation";
import { Dialog, DialogContent, DialogTrigger } from "@/components/ui/dialog";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Loader2, LogIn, Key, AlertCircle, Shield } from "lucide-react";

interface SignInModalProps {
  children: React.ReactNode;
}

export function SignInModal({ children }: SignInModalProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [inviteLink, setInviteLink] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");
  const { login } = useAuth();
  const router = useRouter();

  const extractTokenFromLink = (link: string): string | null => {
    try {
      // Handle full URLs like http://localhost:3001/dashboard?token=invite_xxx
      const url = new URL(link);
      const token = url.searchParams.get("token");
      if (token) return token;
    } catch {
      // If URL parsing fails, check if it's just a token
      if (link.startsWith("invite_")) {
        return link;
      }
    }
    return null;
  };

  const handleSignIn = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setIsLoading(true);

    try {
      const token = extractTokenFromLink(inviteLink.trim());

      if (!token) {
        setError(
          "Please enter a valid invite link or token (e.g., invite_xxxxx)"
        );
        setIsLoading(false);
        return;
      }

      const success = await login(token);

      if (success) {
        setIsOpen(false);
        setInviteLink("");
        router.push("/dashboard");
      } else {
        setError("Invalid invite link or token. Please check and try again.");
      }
    } catch (err) {
      console.error("Sign in error:", err);
      setError("An error occurred during sign in. Please try again.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-md p-0 gap-0">
        <Card className="border-0 shadow-none">
          <CardHeader className="p-4 sm:p-6 pb-4">
            <div className="flex items-center gap-2 mb-2">
              <Shield className="h-5 w-5 text-primary" />
              <Badge variant="secondary" className="text-xs">
                BETA ACCESS
              </Badge>
            </div>
            <CardTitle className="text-xl sm:text-2xl">
              Sign In to PayPerCrawl
            </CardTitle>
            <CardDescription className="text-sm sm:text-base">
              Enter your invite link or token to access your beta dashboard.
            </CardDescription>
          </CardHeader>

          <CardContent className="p-4 sm:p-6 pt-0">
            <form onSubmit={handleSignIn} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="invite-link" className="text-sm font-medium">
                  Invite Link or Token
                </Label>
                <Input
                  id="invite-link"
                  type="text"
                  placeholder="Paste your invite link or token here..."
                  value={inviteLink}
                  onChange={(e) => setInviteLink(e.target.value)}
                  disabled={isLoading}
                  className="w-full"
                />
                <p className="text-xs text-muted-foreground">
                  You can paste the full link (e.g.,{" "}
                  <code className="text-xs bg-muted px-1 py-0.5 rounded">
                    http://localhost:3001/dashboard?token=invite_xxx
                  </code>
                  ) or just the token (
                  <code className="text-xs bg-muted px-1 py-0.5 rounded">
                    invite_xxx
                  </code>
                  )
                </p>
              </div>

              {error && (
                <Alert variant="destructive">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription className="text-sm">
                    {error}
                  </AlertDescription>
                </Alert>
              )}

              <div className="flex gap-2 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setIsOpen(false)}
                  disabled={isLoading}
                  className="flex-1"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  disabled={isLoading || !inviteLink.trim()}
                  className="flex-1"
                >
                  {isLoading ? (
                    <>
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                      Signing in...
                    </>
                  ) : (
                    <>
                      <Key className="h-4 w-4 mr-2" />
                      Sign In
                    </>
                  )}
                </Button>
              </div>
            </form>

            <div className="border-t pt-4 mt-6">
              <div className="text-center">
                <p className="text-xs text-muted-foreground">
                  Don't have an invite?{" "}
                  <Button
                    variant="link"
                    className="h-auto p-0 text-xs text-primary font-medium"
                    onClick={() => {
                      setIsOpen(false);
                      router.push("/waitlist");
                    }}
                  >
                    Join our waitlist
                  </Button>{" "}
                  to get beta access.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </DialogContent>
    </Dialog>
  );
}
