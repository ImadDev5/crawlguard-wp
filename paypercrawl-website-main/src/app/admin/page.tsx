"use client";

import { useState, useEffect } from "react";
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import {
  Shield,
  Users,
  Mail,
  FileText,
  Send,
  Loader2,
  Eye,
  EyeOff,
  Copy,
  RefreshCw,
} from "lucide-react";
import { toast } from "sonner";
import { formatDate } from "@/lib/utils";
import { ModeToggle } from "@/components/mode-toggle";
import Link from "next/link";

interface Application {
  id: string;
  name: string;
  email: string;
  position: string;
  phone?: string;
  website?: string;
  coverLetter?: string;
  status: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

interface WaitlistEntry {
  id: string;
  name: string;
  email: string;
  website?: string;
  companySize?: string;
  useCase?: string;
  status: string;
  inviteToken?: string;
  invitedAt?: string;
  createdAt: string;
  updatedAt: string;
}

interface WaitlistPagination {
  page: number;
  limit: number;
  total: number;
  pages: number;
}

export default function AdminDashboard() {
  const [adminKey, setAdminKey] = useState("");
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [showKey, setShowKey] = useState(false);
  const [applications, setApplications] = useState<Application[]>([]);
  const [waitlistEntries, setWaitlistEntries] = useState<WaitlistEntry[]>([]);
  const [waitlistPagination, setWaitlistPagination] =
    useState<WaitlistPagination>({
      page: 1,
      limit: 50,
      total: 0,
      pages: 0,
    });
  const [loading, setLoading] = useState(false);
  const [waitlistLoading, setWaitlistLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [viewMode, setViewMode] = useState<"pagination" | "loadmore">(
    "pagination"
  );
  const [selectedApplication, setSelectedApplication] =
    useState<Application | null>(null);
  const [applicationNotes, setApplicationNotes] = useState("");
  const [applicationStatus, setApplicationStatus] = useState("");

  useEffect(() => {
    const key = localStorage.getItem("adminKey");
    if (key) {
      setAdminKey(key);
    }
  }, []);

  const authenticate = () => {
    const expectedAdminKey =
      process.env.NEXT_PUBLIC_ADMIN_KEY || "paypercrawl_admin_2025_secure_key";
    if (adminKey === expectedAdminKey) {
      localStorage.setItem("adminKey", adminKey);
      setIsAuthenticated(true);
      toast.success("Authentication successful");
      loadData();
    } else {
      toast.error("Invalid admin key");
    }
  };

  const loadData = async () => {
    setLoading(true);
    try {
      const [appsResponse] = await Promise.all([
        fetch("/api/admin/applications", {
          headers: {
            Authorization: `Bearer ${adminKey}`,
          },
        }),
      ]);

      if (appsResponse.ok) {
        const appsData = await appsResponse.json();
        setApplications(appsData.applications);
      }

      // Load waitlist separately with pagination
      await loadWaitlist(1);
    } catch (error) {
      console.error("Error loading data:", error);
      toast.error("Failed to load data");
    } finally {
      setLoading(false);
    }
  };

  const loadWaitlist = async (
    page: number = waitlistPagination.page,
    limit: number = waitlistPagination.limit,
    search: string = searchTerm,
    append: boolean = false,
    status: string = statusFilter
  ) => {
    setWaitlistLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: limit.toString(),
      });

      if (search.trim()) {
        params.append("search", search.trim());
      }

      if (status && status !== "all") {
        params.append("status", status);
      }

      const waitlistResponse = await fetch(
        `/api/admin/waitlist?${params.toString()}`,
        {
          headers: {
            Authorization: `Bearer ${adminKey}`,
          },
        }
      );

      if (waitlistResponse.ok) {
        const waitlistData = await waitlistResponse.json();
        if (append) {
          setWaitlistEntries((prev) => [
            ...prev,
            ...waitlistData.waitlistEntries,
          ]);
        } else {
          setWaitlistEntries(waitlistData.waitlistEntries);
        }
        setWaitlistPagination(waitlistData.pagination);
      }
    } catch (error) {
      console.error("Error loading waitlist:", error);
      toast.error("Failed to load waitlist");
    } finally {
      setWaitlistLoading(false);
    }
  };

  const updateApplicationStatus = async () => {
    if (!selectedApplication) return;

    try {
      const response = await fetch(`/api/admin/applications`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${adminKey}`,
        },
        body: JSON.stringify({
          id: selectedApplication.id,
          status: applicationStatus,
          notes: applicationNotes,
        }),
      });

      if (response.ok) {
        toast.success("Application updated successfully");
        loadData();
        setSelectedApplication(null);
      } else {
        toast.error("Failed to update application");
      }
    } catch (error) {
      console.error("Error updating application:", error);
      toast.error("An error occurred while updating the application");
    }
  };

  const sendInvite = async (email: string) => {
    try {
      const response = await fetch("/api/waitlist/invite", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          email,
          adminKey,
        }),
      });

      if (response.ok) {
        toast.success("Invite sent successfully");
        loadWaitlist(); // Reload current page
      } else {
        const errorData = await response.json();
        toast.error(`Failed to send invite: ${errorData.error}`);
      }
    } catch (error) {
      console.error("Error sending invite:", error);
      toast.error("An error occurred while sending the invite");
    }
  };

  const handlePageChange = (newPage: number) => {
    loadWaitlist(
      newPage,
      waitlistPagination.limit,
      searchTerm,
      false,
      statusFilter
    );
  };

  const handleLimitChange = (newLimit: string) => {
    const limit = parseInt(newLimit);
    setWaitlistPagination((prev) => ({ ...prev, limit, page: 1 }));
    loadWaitlist(1, limit, searchTerm, false, statusFilter);
  };

  const handleSearch = (term: string) => {
    setSearchTerm(term);
    setWaitlistPagination((prev) => ({ ...prev, page: 1 }));
    loadWaitlist(1, waitlistPagination.limit, term, false, statusFilter);
  };

  const handleStatusFilter = (status: string) => {
    setStatusFilter(status);
    setWaitlistPagination((prev) => ({ ...prev, page: 1 }));
    loadWaitlist(1, waitlistPagination.limit, searchTerm, false, status);
  };

  const handleLoadMore = () => {
    const nextPage = waitlistPagination.page + 1;
    loadWaitlist(
      nextPage,
      waitlistPagination.limit,
      searchTerm,
      true,
      statusFilter
    );
  };

  useEffect(() => {
    if (selectedApplication) {
      setApplicationNotes(selectedApplication.notes || "");
      setApplicationStatus(selectedApplication.status);
    }
  }, [selectedApplication]);

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background text-foreground p-4">
        <Card className="w-full max-w-md">
          <CardHeader>
            <CardTitle className="text-2xl flex items-center">
              <Shield className="mr-2 h-6 w-6" /> Admin Access
            </CardTitle>
            <CardDescription>
              Enter your secret key to access the dashboard.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="admin-key">Admin Key</Label>
                <div className="relative">
                  <Input
                    id="admin-key"
                    type={showKey ? "text" : "password"}
                    value={adminKey}
                    onChange={(e) => setAdminKey(e.target.value)}
                    onKeyPress={(e) => e.key === "Enter" && authenticate()}
                    placeholder="Enter your secret key"
                  />
                  <Button
                    variant="ghost"
                    size="icon"
                    className="absolute top-1/2 right-2 -translate-y-1/2 h-7 w-7"
                    onClick={() => setShowKey(!showKey)}
                  >
                    {showKey ? (
                      <EyeOff className="h-4 w-4" />
                    ) : (
                      <Eye className="h-4 w-4" />
                    )}
                  </Button>
                </div>
              </div>
              <Button onClick={authenticate} className="w-full">
                Authenticate
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background text-foreground">
      <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container flex h-14 items-center">
          <Link href="/" className="mr-6 flex items-center space-x-2">
            <Shield className="h-6 w-6" />
            <span className="font-bold">PayperCrawl Admin</span>
          </Link>
          <div className="flex flex-1 items-center justify-end space-x-4">
            <ModeToggle />
            <Button
              variant="outline"
              onClick={() => {
                localStorage.removeItem("adminKey");
                setIsAuthenticated(false);
                toast.info("Logged out");
              }}
            >
              Logout
            </Button>
          </div>
        </div>
      </header>

      <main className="container mx-auto p-4 sm:p-6 lg:p-8">
        <Tabs defaultValue="applications">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="applications">
              <FileText className="mr-2 h-4 w-4" />
              Applications ({applications.length})
            </TabsTrigger>
            <TabsTrigger value="waitlist">
              <Users className="mr-2 h-4 w-4" />
              Waitlist ({waitlistPagination.total})
            </TabsTrigger>
          </TabsList>
          <TabsContent value="applications">
            <Card>
              <CardHeader>
                <CardTitle>Beta Applications</CardTitle>
                <CardDescription>
                  Review and manage applications for the beta program.
                </CardDescription>
              </CardHeader>
              <CardContent>
                {loading ? (
                  <div className="flex justify-center items-center h-40">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : (
                  <div className="grid md:grid-cols-3 gap-6">
                    <div className="md:col-span-1">
                      <h3 className="font-bold mb-4">Applicants</h3>
                      <div className="space-y-2 max-h-[600px] overflow-y-auto">
                        {applications.map((app) => (
                          <Button
                            key={app.id}
                            variant={
                              selectedApplication?.id === app.id
                                ? "secondary"
                                : "ghost"
                            }
                            className="w-full justify-start h-auto py-2"
                            onClick={() => setSelectedApplication(app)}
                          >
                            <div className="text-left">
                              <p className="font-semibold">{app.name}</p>
                              <p className="text-sm text-muted-foreground">
                                {app.email}
                              </p>
                              <Badge variant="outline" className="mt-1">
                                {app.status}
                              </Badge>
                            </div>
                          </Button>
                        ))}
                      </div>
                    </div>
                    <div className="md:col-span-2">
                      {selectedApplication ? (
                        <Card>
                          <CardHeader>
                            <CardTitle>{selectedApplication.name}</CardTitle>
                            <CardDescription>
                              Applied on{" "}
                              {formatDate(
                                new Date(selectedApplication.createdAt)
                              )}
                            </CardDescription>
                          </CardHeader>
                          <CardContent>
                            <div className="space-y-4">
                              <p>
                                <strong>Email:</strong>{" "}
                                {selectedApplication.email}
                              </p>
                              <p>
                                <strong>Phone:</strong>{" "}
                                {selectedApplication.phone || "N/A"}
                              </p>
                              <p>
                                <strong>Website:</strong>{" "}
                                <a
                                  href={selectedApplication.website}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="text-primary hover:underline"
                                >
                                  {selectedApplication.website}
                                </a>
                              </p>
                              <p>
                                <strong>Position:</strong>{" "}
                                {selectedApplication.position}
                              </p>
                              <div>
                                <p>
                                  <strong>Cover Letter:</strong>
                                </p>
                                <p className="text-muted-foreground p-2 border rounded-md bg-muted/50">
                                  {selectedApplication.coverLetter || "N/A"}
                                </p>
                              </div>
                              <div className="space-y-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                  value={applicationStatus}
                                  onValueChange={setApplicationStatus}
                                >
                                  <SelectTrigger>
                                    <SelectValue placeholder="Set status" />
                                  </SelectTrigger>
                                  <SelectContent
                                    className="!bg-background !bg-opacity-100 backdrop-blur-none border border-border shadow-lg z-50"
                                    style={{
                                      backgroundColor: "hsl(var(--background))",
                                      opacity: 1,
                                    }}
                                  >
                                    <SelectItem value="pending">
                                      Pending
                                    </SelectItem>
                                    <SelectItem value="reviewed">
                                      Reviewed
                                    </SelectItem>
                                    <SelectItem value="approved">
                                      Approved
                                    </SelectItem>
                                    <SelectItem value="rejected">
                                      Rejected
                                    </SelectItem>
                                  </SelectContent>
                                </Select>
                              </div>
                              <div className="space-y-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                  id="notes"
                                  value={applicationNotes}
                                  onChange={(e) =>
                                    setApplicationNotes(e.target.value)
                                  }
                                  placeholder="Add internal notes..."
                                />
                              </div>
                              <Button onClick={updateApplicationStatus}>
                                Update Application
                              </Button>
                            </div>
                          </CardContent>
                        </Card>
                      ) : (
                        <div className="flex items-center justify-center h-full text-muted-foreground">
                          <p>Select an application to view details</p>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
          <TabsContent value="waitlist">
            <Card>
              <CardHeader>
                <div className="flex justify-between items-center">
                  <div>
                    <CardTitle>Waitlist Entries</CardTitle>
                    <CardDescription>
                      Manage users who have joined the waitlist.
                    </CardDescription>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Label
                      htmlFor="entries-per-page"
                      className="text-sm font-medium"
                    >
                      Per page:
                    </Label>
                    <Select
                      value={waitlistPagination.limit.toString()}
                      onValueChange={handleLimitChange}
                    >
                      <SelectTrigger className="w-20">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent
                        className="!bg-background !bg-opacity-100 backdrop-blur-none border border-border shadow-lg z-50"
                        style={{
                          backgroundColor: "hsl(var(--background))",
                          opacity: 1,
                        }}
                      >
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="mt-4 flex justify-between items-center">
                  <div className="flex items-center space-x-4">
                    <div className="relative max-w-sm">
                      <Input
                        placeholder="Search by name, email, or website..."
                        value={searchTerm}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="pl-4"
                      />
                    </div>
                    <div className="flex items-center space-x-2">
                      <Label className="text-sm font-medium">Status:</Label>
                      <Select
                        value={statusFilter}
                        onValueChange={handleStatusFilter}
                      >
                        <SelectTrigger className="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent
                          className="!bg-background !bg-opacity-100 backdrop-blur-none border border-border shadow-lg z-50"
                          style={{
                            backgroundColor: "hsl(var(--background))",
                            opacity: 1,
                          }}
                        >
                          <SelectItem value="all">All</SelectItem>
                          <SelectItem value="pending">Pending</SelectItem>
                          <SelectItem value="invited">Invited</SelectItem>
                          <SelectItem value="accepted">Accepted</SelectItem>
                          <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        loadWaitlist(
                          1,
                          waitlistPagination.limit,
                          searchTerm,
                          false,
                          statusFilter
                        )
                      }
                      disabled={waitlistLoading}
                    >
                      <RefreshCw
                        className={`h-4 w-4 mr-2 ${waitlistLoading ? "animate-spin" : ""}`}
                      />
                      Refresh
                    </Button>
                    <Label className="text-sm font-medium">View:</Label>
                    <Select
                      value={viewMode}
                      onValueChange={(value: "pagination" | "loadmore") =>
                        setViewMode(value)
                      }
                    >
                      <SelectTrigger className="w-32">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent
                        className="!bg-background !bg-opacity-100 backdrop-blur-none border border-border shadow-lg z-50"
                        style={{
                          backgroundColor: "hsl(var(--background))",
                          opacity: 1,
                        }}
                      >
                        <SelectItem value="pagination">Pagination</SelectItem>
                        <SelectItem value="loadmore">Load More</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {waitlistLoading ? (
                  <div className="flex justify-center items-center h-40">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : (
                  <div className="space-y-4">
                    {/* Summary Banner */}
                    <div className="bg-muted/50 rounded-lg p-4">
                      <div className="flex justify-between items-center text-sm">
                        <div className="flex items-center space-x-4">
                          <span className="font-medium">
                            Total: {waitlistPagination.total} entries
                          </span>
                          {(searchTerm || statusFilter !== "all") && (
                            <span className="text-muted-foreground">
                              (Filtered results)
                            </span>
                          )}
                        </div>
                        <div className="flex items-center space-x-2 text-muted-foreground">
                          <span>
                            Showing{" "}
                            {waitlistEntries.length > 0
                              ? (waitlistPagination.page - 1) *
                                  waitlistPagination.limit +
                                1
                              : 0}{" "}
                            to{" "}
                            {Math.min(
                              waitlistPagination.page *
                                waitlistPagination.limit,
                              waitlistPagination.total
                            )}{" "}
                            of {waitlistPagination.total} entries
                          </span>
                          <span>•</span>
                          <span>
                            Page {waitlistPagination.page} of{" "}
                            {waitlistPagination.pages}
                          </span>
                        </div>
                      </div>
                    </div>
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Name</TableHead>
                          <TableHead>Email</TableHead>
                          <TableHead>Website</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead>Joined</TableHead>
                          <TableHead>Actions</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {waitlistEntries.length === 0 ? (
                          <TableRow>
                            <TableCell colSpan={6} className="text-center py-8">
                              <div className="flex flex-col items-center space-y-2">
                                <Users className="h-8 w-8 text-muted-foreground" />
                                <p className="text-muted-foreground">
                                  {searchTerm || statusFilter !== "all"
                                    ? "No waitlist entries match your criteria"
                                    : "No waitlist entries found"}
                                </p>
                                {(searchTerm || statusFilter !== "all") && (
                                  <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                      setSearchTerm("");
                                      setStatusFilter("all");
                                      loadWaitlist(
                                        1,
                                        waitlistPagination.limit,
                                        "",
                                        false,
                                        "all"
                                      );
                                    }}
                                  >
                                    Clear Filters
                                  </Button>
                                )}
                              </div>
                            </TableCell>
                          </TableRow>
                        ) : (
                          waitlistEntries.map((entry) => (
                            <TableRow key={entry.id}>
                              <TableCell>{entry.name}</TableCell>
                              <TableCell>{entry.email}</TableCell>
                              <TableCell>
                                <a
                                  href={entry.website}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="text-primary hover:underline"
                                >
                                  {entry.website}
                                </a>
                              </TableCell>
                              <TableCell>
                                <Badge
                                  variant={
                                    entry.status === "invited"
                                      ? "default"
                                      : "secondary"
                                  }
                                >
                                  {entry.status}
                                </Badge>
                              </TableCell>
                              <TableCell>
                                {formatDate(new Date(entry.createdAt))}
                              </TableCell>
                              <TableCell>
                                {entry.status !== "invited" &&
                                entry.status !== "accepted" ? (
                                  <Button
                                    size="sm"
                                    onClick={() => sendInvite(entry.email)}
                                  >
                                    <Send className="mr-2 h-4 w-4" />
                                    Send Invite
                                  </Button>
                                ) : entry.inviteToken ? (
                                  <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                      const inviteUrl = `${window.location.origin}/dashboard?token=${entry.inviteToken}`;
                                      navigator.clipboard.writeText(inviteUrl);
                                      toast.success(
                                        "Invite link copied to clipboard!"
                                      );
                                    }}
                                  >
                                    <Copy className="mr-2 h-4 w-4" />
                                    Copy Link
                                  </Button>
                                ) : (
                                  <span className="text-muted-foreground text-sm">
                                    No action needed
                                  </span>
                                )}
                              </TableCell>
                            </TableRow>
                          ))
                        )}
                      </TableBody>
                    </Table>

                    {/* Pagination or Load More Controls */}
                    {viewMode === "pagination" &&
                      waitlistPagination.pages > 1 && (
                        <div className="flex justify-between items-center pt-4">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                              handlePageChange(waitlistPagination.page - 1)
                            }
                            disabled={waitlistPagination.page === 1}
                          >
                            Previous
                          </Button>

                          <div className="flex items-center space-x-2">
                            {/* Show page numbers */}
                            {Array.from(
                              { length: Math.min(5, waitlistPagination.pages) },
                              (_, i) => {
                                let pageNum;
                                if (waitlistPagination.pages <= 5) {
                                  pageNum = i + 1;
                                } else if (waitlistPagination.page <= 3) {
                                  pageNum = i + 1;
                                } else if (
                                  waitlistPagination.page >=
                                  waitlistPagination.pages - 2
                                ) {
                                  pageNum = waitlistPagination.pages - 4 + i;
                                } else {
                                  pageNum = waitlistPagination.page - 2 + i;
                                }

                                return (
                                  <Button
                                    key={pageNum}
                                    variant={
                                      waitlistPagination.page === pageNum
                                        ? "default"
                                        : "outline"
                                    }
                                    size="sm"
                                    onClick={() => handlePageChange(pageNum)}
                                    className="w-8 h-8 p-0"
                                  >
                                    {pageNum}
                                  </Button>
                                );
                              }
                            )}
                          </div>

                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                              handlePageChange(waitlistPagination.page + 1)
                            }
                            disabled={
                              waitlistPagination.page ===
                              waitlistPagination.pages
                            }
                          >
                            Next
                          </Button>
                        </div>
                      )}

                    {viewMode === "loadmore" &&
                      waitlistPagination.page < waitlistPagination.pages && (
                        <div className="flex justify-center pt-4">
                          <Button
                            variant="outline"
                            onClick={handleLoadMore}
                            disabled={waitlistLoading}
                          >
                            {waitlistLoading ? (
                              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : null}
                            Load More (
                            {waitlistPagination.total - waitlistEntries.length}{" "}
                            remaining)
                          </Button>
                        </div>
                      )}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </main>
    </div>
  );
}
