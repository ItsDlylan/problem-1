import { useState, useRef, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import { Send, Loader2 } from "lucide-react";
import { sendChatMessage, confirmAppointment } from "@/services/chatApi";
import type { ChatMessage, ExtractedAppointmentDetails } from "@/types/chat";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

interface ChatbotPanelProps {
  isOpen: boolean;
  onClose: () => void;
}

export function ChatbotPanel({ isOpen, onClose }: ChatbotPanelProps) {
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      role: "assistant",
      content: "Hello! How can I help you schedule an appointment today?",
    },
  ]);
  const [inputValue, setInputValue] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [extractedDetails, setExtractedDetails] =
    useState<ExtractedAppointmentDetails | null>(null);
  const [isConfirming, setIsConfirming] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Auto-scroll to bottom when new messages arrive
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  // Focus input when panel opens
  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 100);
    }
  }, [isOpen]);

  const handleSendMessage = async () => {
    const message = inputValue.trim();
    if (!message || isLoading) return;

    // Add user message to UI immediately
    const userMessage: ChatMessage = { role: "user", content: message };
    setMessages((prev) => [...prev, userMessage]);
    setInputValue("");
    setIsLoading(true);
    setExtractedDetails(null);

    try {
      const response = await sendChatMessage(message);

      // Add assistant response
      const assistantMessage: ChatMessage = {
        role: "assistant",
        content: response.message,
      };
      setMessages((prev) => [...prev, assistantMessage]);

      // Update extracted details if available
      if (response.extractedDetails) {
        setExtractedDetails(response.extractedDetails);
      }
    } catch (error) {
      const errorMessage: ChatMessage = {
        role: "assistant",
        content:
          error instanceof Error
            ? error.message
            : "Sorry, I encountered an error. Please try again.",
      };
      setMessages((prev) => [...prev, errorMessage]);
    } finally {
      setIsLoading(false);
    }
  };

  const handleConfirmAppointment = async () => {
    if (!extractedDetails || isConfirming) return;

    setIsConfirming(true);

    try {
      const response = await confirmAppointment({
        serviceOfferingId: extractedDetails.serviceOfferingId,
        datetime: extractedDetails.datetime,
      });

      if (response.success && response.appointment) {
        const successMessage: ChatMessage = {
          role: "assistant",
          content: `Great! Your appointment has been confirmed for ${new Date(response.appointment.startAt).toLocaleString()} with ${response.appointment.doctor.name} at ${response.appointment.facility.name}. See you then!`,
        };
        setMessages((prev) => [...prev, successMessage]);
        setExtractedDetails(null);

        // Close panel after a short delay
        setTimeout(() => {
          onClose();
          // Reset state
          setMessages([
            {
              role: "assistant",
              content:
                "Hello! How can I help you schedule an appointment today?",
            },
          ]);
          setExtractedDetails(null);
        }, 2000);
      } else {
        throw new Error(response.message || "Failed to confirm appointment");
      }
    } catch (error) {
      const errorMessage: ChatMessage = {
        role: "assistant",
        content:
          error instanceof Error
            ? error.message
            : "Sorry, I encountered an error confirming your appointment. Please try again.",
      };
      setMessages((prev) => [...prev, errorMessage]);
    } finally {
      setIsConfirming(false);
    }
  };

  const handleKeyPress = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent className="flex flex-col w-full sm:w-96">
        <SheetHeader>
          <SheetTitle>Scheduling Assistant</SheetTitle>
        </SheetHeader>
        <div className="flex-grow overflow-y-auto p-4 space-y-4">
          {messages.map((msg, index) => (
            <div
              key={index}
              className={`flex items-end gap-2 ${
                msg.role === "user" ? "justify-end" : "justify-start"
              }`}
            >
              <div
                className={`rounded-lg p-3 max-w-[80%] ${
                  msg.role === "user"
                    ? "bg-gray-200 dark:bg-gray-700"
                    : "bg-blue-500 text-white"
                }`}
              >
                <p className="text-sm whitespace-pre-wrap">{msg.content}</p>
              </div>
            </div>
          ))}
          {isLoading && (
            <div className="flex items-end gap-2 justify-start">
              <div className="rounded-lg bg-blue-500 p-3 text-white">
                <Loader2 className="h-4 w-4 animate-spin" />
              </div>
            </div>
          )}
          {extractedDetails && (
            <Card className="mt-4">
              <CardHeader>
                <CardTitle>Appointment Details</CardTitle>
                <CardDescription>
                  Please confirm your appointment
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-2">
                <div>
                  <p className="text-sm font-medium">Service:</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    {extractedDetails.serviceOffering.service.name}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium">Doctor:</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    {extractedDetails.serviceOffering.doctor.name}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium">Facility:</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    {extractedDetails.serviceOffering.facility.name}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium">Date & Time:</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    {new Date(extractedDetails.datetime).toLocaleString()}
                  </p>
                </div>
              </CardContent>
              <CardFooter className="flex gap-2">
                <Button
                  onClick={handleConfirmAppointment}
                  disabled={isConfirming}
                  className="flex-1"
                >
                  {isConfirming ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Confirming...
                    </>
                  ) : (
                    "Confirm Appointment"
                  )}
                </Button>
                <Button
                  variant="outline"
                  onClick={() => setExtractedDetails(null)}
                  disabled={isConfirming}
                >
                  Cancel
                </Button>
              </CardFooter>
            </Card>
          )}
          <div ref={messagesEndRef} />
        </div>
        <div className="flex items-center gap-2 border-t p-4">
          <Input
            ref={inputRef}
            placeholder="Type your message..."
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            onKeyPress={handleKeyPress}
            disabled={isLoading || isConfirming}
          />
          <Button
            variant="ghost"
            size="icon"
            onClick={handleSendMessage}
            disabled={!inputValue.trim() || isLoading || isConfirming}
          >
            {isLoading ? (
              <Loader2 className="h-5 w-5 animate-spin" />
            ) : (
              <Send className="h-5 w-5" />
            )}
          </Button>
        </div>
      </SheetContent>
    </Sheet>
  );
}
