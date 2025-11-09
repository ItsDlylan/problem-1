export interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
}

export interface ExtractedAppointmentDetails {
  service: string;
  datetime: string;
  serviceOfferingId: number;
  isAvailable?: boolean;
  alternativeTimes?: Array<{
    startAt: string;
    endAt: string;
  }>;
  serviceOffering: {
    id: number;
    service: {
      id: number;
      name: string;
      description: string | null;
    };
    doctor: {
      id: number;
      name: string;
    };
    facility: {
      id: number;
      name: string;
    };
  };
}

export interface ChatResponse {
  message: string;
  extractedDetails: ExtractedAppointmentDetails | null;
}

export interface ConfirmAppointmentRequest {
  serviceOfferingId: number;
  datetime: string;
}

export interface ConfirmAppointmentResponse {
  success: boolean;
  appointment?: {
    id: number;
    startAt: string;
    endAt: string;
    status: string;
    service: {
      name: string;
      description: string | null;
    };
    doctor: {
      name: string;
    };
    facility: {
      name: string;
    };
  };
  message?: string;
}

