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
import { Loader2, CheckCircle, AlertCircle } from "lucide-react";
import { toast } from "sonner";

const applicationSchema = z.object({
  name: z.string().min(2, "Name must be at least 2 characters"),
  email: z.string().email("Invalid email address"),
  position: z.string().min(1, "Please select a position"),
  phone: z.string().optional(),
  website: z.string().url().optional().or(z.literal("")),
  coverLetter: z.string().optional(),
});

type ApplicationFormData = z.infer<typeof applicationSchema>;

interface ApplicationFormProps {
  position?: string;
  onSuccess?: () => void;
}

export function ApplicationForm({ position, onSuccess }: ApplicationFormProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
    reset,
  } = useForm<ApplicationFormData>({
    resolver: zodResolver(applicationSchema),
    defaultValues: {
      position: position || "",
    },
  });

  const selectedPosition = watch("position");

  const positions = [
    "Senior WordPress Developer",
    "Cloudflare Workers Specialist",
    "Full Stack Developer",
    "DevOps Engineer",
    "Business Development Manager",
    "Sales Representative",
    "Partnership Manager",
    "Content Marketing Manager",
    "Growth Marketer",
    "Community Manager",
    "Product Manager",
    "Customer Success Manager",
  ];

  const onSubmit = async (data: ApplicationFormData) => {
    setIsSubmitting(true);

    try {
      const response = await fetch("/api/applications/submit", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || "Failed to submit application");
      }

      setIsSubmitted(true);
      toast.success("Application submitted successfully!");
      reset();
      onSuccess?.();
    } catch (error) {
      console.error("Application submission error:", error);
      toast.error(
        error instanceof Error ? error.message : "Failed to submit application"
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
              Application Submitted!
            </h3>
            <p className="text-muted-foreground mb-4">
              Thank you for your interest in PayPerCrawl. We'll review your
              application and get back to you within 2-3 business days.
            </p>
            <Button onClick={() => setIsSubmitted(false)} variant="outline">
              Submit Another Application
            </Button>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>Apply for a Position</CardTitle>
        <CardDescription>
          Fill out the form below to submit your application. We'll get back to
          you soon!
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

          <div className="space-y-2">
            <Label htmlFor="position">Position *</Label>
            <Select
              value={selectedPosition}
              onValueChange={(value) => setValue("position", value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select a position" />
              </SelectTrigger>
              <SelectContent>
                {positions.map((pos) => (
                  <SelectItem key={pos} value={pos}>
                    {pos}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.position && (
              <p className="text-sm text-red-600 flex items-center gap-1">
                <AlertCircle className="h-3 w-3" />
                {errors.position.message}
              </p>
            )}
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="phone">Phone Number</Label>
              <Input
                id="phone"
                {...register("phone")}
                placeholder="+1 (555) 123-4567"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="website">Website/Portfolio</Label>
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
          </div>

          <div className="space-y-2">
            <Label htmlFor="coverLetter">Cover Letter</Label>
            <Textarea
              id="coverLetter"
              {...register("coverLetter")}
              placeholder="Tell us why you're interested in this position and what makes you a great fit..."
              rows={6}
            />
          </div>

          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                Submitting Application...
              </>
            ) : (
              "Submit Application"
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
