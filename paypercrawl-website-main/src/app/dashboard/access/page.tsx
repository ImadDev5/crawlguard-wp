import { Suspense } from "react";
import AccessContent from "./AccessContent";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Loader2 } from "lucide-react";

export const dynamic = "force-dynamic";

function AccessFallback() {
  return (
    <div className="min-h-screen bg-background flex items-center justify-center">
      <Card className="w-[400px]">
        <CardHeader className="text-center">
          <div className="flex items-center justify-center space-x-2 mb-4">
            <span className="text-2xl font-bold">PayPerCrawl</span>
          </div>
          <CardTitle>Processing Access</CardTitle>
          <CardDescription>
            Please wait while we process your invitation token...
          </CardDescription>
        </CardHeader>
        <CardContent className="flex justify-center">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </CardContent>
      </Card>
    </div>
  );
}

export default function DashboardAccessPage() {
  return (
    <Suspense fallback={<AccessFallback />}>
      <AccessContent />
    </Suspense>
  );
}
