"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Loader2, CheckCircle, AlertCircle, Users } from "lucide-react";
import { toast } from "sonner";

const waitlistSchema = z.object({
  name: z.string().min(2, "Name must be at least 2 characters"),
  email: z.string().email("Invalid email address"),
  website: z.string().url().optional().or(z.literal("")),
  companySize: z.enum(["small", "medium", "large"]).optional(),
  useCase: z.string().optional(),
});

type WaitlistFormData = z.infer<typeof waitlistSchema>;

interface WaitlistFormProps {
  onSuccess?: (position: number) => void;
}

export function WaitlistForm({ onSuccess }: WaitlistFormProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [waitlistPosition, setWaitlistPosition] = useState<number | null>(null);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
    reset,
  } = useForm<WaitlistFormData>({
    resolver: zodResolver(waitlistSchema),
  });

  const selectedCompanySize = watch("companySize");

  const onSubmit = async (data: WaitlistFormData) => {
    setIsSubmitting(true);

    try {
      const response = await fetch("/api/waitlist/join", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || "Failed to join waitlist");
      }

      setWaitlistPosition(result.position);
      setIsSubmitted(true);
      toast.success("Successfully joined the waitlist!");
      reset();
      onSuccess?.(result.position);
    } catch (error) {
      console.error("Waitlist join error:", error);
      toast.error(
        error instanceof Error ? error.message : "Failed to join waitlist"
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isSubmitted) {
    return (
      <Card className="max-w-md mx-auto">
        <CardContent className="pt-6">
          <div className="text-center">
            <CheckCircle className="h-16 w-16 text-green-600 mx-auto mb-4" />
            <h3 className="text-xl font-semibold mb-2">
              You're on the waitlist! 🎉
            </h3>

            {waitlistPosition && (
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div className="flex items-center justify-center gap-2 text-blue-800">
                  <Users className="h-5 w-5" />
                  <span className="font-semibold">
                    Position #{waitlistPosition}
                  </span>
                </div>
                <p className="text-sm text-blue-600 mt-1">
                  We'll notify you as soon as beta access becomes available!
                </p>
              </div>
            )}

            <p className="text-muted-foreground mb-4">
              Thank you for joining the PayPerCrawl waitlist. You'll receive an
              email confirmation shortly.
            </p>
            <Button onClick={() => setIsSubmitted(false)} variant="outline">
              Join Another Email
            </Button>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>Join the PayPerCrawl Beta Waitlist</CardTitle>
        <CardDescription>
          Be among the first to monetize your AI bot traffic. We'll notify you
          when beta access is available.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          <div className="grid md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="name">Full Name *</Label>
              <Input id="name" {...register("name")} placeholder="John Doe" />
              {errors.name && (
                <p className="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {errors.name.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Email Address *</Label>
              <Input
                id="email"
                type="email"
                {...register("email")}
                placeholder="john@example.com"
              />
              {errors.email && (
                <p className="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {errors.email.message}
                </p>
              )}
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="website">Website URL</Label>
              <Input
                id="website"
                {...register("website")}
                placeholder="https://yourwebsite.com"
              />
              {errors.website && (
                <p className="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {errors.website.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="companySize">Company Size</Label>
              <Select
                value={selectedCompanySize}
                onValueChange={(value) =>
                  setValue("companySize", value as "small" | "medium" | "large")
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select company size" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="small">Small (1-10 employees)</SelectItem>
                  <SelectItem value="medium">
                    Medium (11-100 employees)
                  </SelectItem>
                  <SelectItem value="large">Large (100+ employees)</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="useCase">How do you plan to use PayPerCrawl?</Label>
            <Textarea
              id="useCase"
              {...register("useCase")}
              placeholder="Tell us about your website, content type, and how you'd like to monetize AI bot traffic..."
              rows={4}
            />
          </div>

          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                Joining Waitlist...
              </>
            ) : (
              "Join Beta Waitlist"
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
