"use client";

import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  ReactNode,
} from "react";

interface User {
  email: string;
  name: string;
  website?: string;
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (token: string) => Promise<boolean>;
  logout: () => void;
  checkAuthStatus: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  const checkAuthStatus = async () => {
    try {
      // Check for token in URL params first (for new invitations)
      const urlParams = new URLSearchParams(window.location.search);
      const urlToken = urlParams.get("token");

      // Check if there's a token in cookies
      const cookieToken = document.cookie
        .split(";")
        .find((row) => row.trim().startsWith("invite_token="))
        ?.split("=")[1];

      console.log("AuthContext Debug:", { urlToken, cookieToken });

      const tokenToUse = urlToken || cookieToken;

      if (!tokenToUse) {
        setIsAuthenticated(false);
        setUser(null);
        setIsLoading(false);
        return;
      }

      // Validate the token with the API
      const response = await fetch("/api/waitlist/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token: tokenToUse }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.valid) {
          setIsAuthenticated(true);
          setUser(data.user);

          // If token came from URL, store it in cookie
          if (urlToken) {
            document.cookie = `invite_token=${urlToken}; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Lax`;
          }
        } else {
          setIsAuthenticated(false);
          setUser(null);
          // Clear invalid token
          document.cookie =
            "invite_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
      } else {
        setIsAuthenticated(false);
        setUser(null);
        // Clear invalid token
        document.cookie =
          "invite_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
      }
    } catch (error) {
      console.error("Error checking auth status:", error);
      setIsAuthenticated(false);
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (token: string): Promise<boolean> => {
    try {
      const response = await fetch("/api/waitlist/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.valid) {
          setIsAuthenticated(true);
          setUser(data.user);

          // Set the cookie
          document.cookie = `invite_token=${token}; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Lax`;

          return true;
        }
      }
      return false;
    } catch (error) {
      console.error("Login error:", error);
      return false;
    }
  };

  const logout = () => {
    setIsAuthenticated(false);
    setUser(null);
    // Clear the cookie
    document.cookie =
      "invite_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
  };

  useEffect(() => {
    checkAuthStatus();
  }, []);

  // Re-check auth status when URL changes (to pick up new tokens)
  useEffect(() => {
    const handleUrlChange = () => {
      checkAuthStatus();
    };

    // Listen for popstate events (back/forward button)
    window.addEventListener("popstate", handleUrlChange);

    // Also check when the component mounts or when the pathname changes
    if (window.location.search.includes("token=")) {
      checkAuthStatus();
    }

    return () => {
      window.removeEventListener("popstate", handleUrlChange);
    };
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuthenticated,
        isLoading,
        login,
        logout,
        checkAuthStatus,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}
