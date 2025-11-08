export type AppointmentStatus = 'upcoming' | 'complete' | 'cancelled' | 'no show';

export interface Appointment {
    id: string;
    doctorName: string;
    facilityName: string;
    facilityLocation: string;
    datetime: string;
    serviceCode: {
        code: string;
        description: string;
    };
    waitlist: number;
    status: AppointmentStatus;
}

export const mockAppointments: Appointment[] = [
    {
        id: '1',
        doctorName: 'Dr. Sarah Connor',
        facilityName: 'General Hospital',
        facilityLocation: '123 Main St, Anytown, USA',
        datetime: '2025-11-20T09:00:00',
        serviceCode: {
            code: 'A927',
            description: 'General Checkup',
        },
        waitlist: 0,
        status: 'upcoming',
    },
    {
        id: '2',
        doctorName: 'Dr. John Doe',
        facilityName: 'City Clinic',
        facilityLocation: '456 Oak Ave, Anytown, USA',
        datetime: '2025-11-21T11:30:00',
        serviceCode: {
            code: 'B301',
            description: 'Dental Cleaning',
        },
        waitlist: 2,
        status: 'upcoming',
    },
    {
        id: '3',
        doctorName: 'Dr. Emily Carter',
        facilityName: 'General Hospital',
        facilityLocation: '123 Main St, Anytown, USA',
        datetime: '2025-11-15T14:00:00',
        serviceCode: {
            code: 'C123',
            description: 'X-Ray',
        },
        waitlist: 0,
        status: 'complete',
    },
    {
        id: '4',
        doctorName: 'Dr. Michael Smith',
        facilityName: 'Ortho Associates',
        facilityLocation: '789 Pine St, Anytown, USA',
        datetime: '2025-11-18T10:00:00',
        serviceCode: {
            code: 'D456',
            description: 'Orthopedic Consultation',
        },
        waitlist: 0,
        status: 'complete',
    },
    {
        id: '5',
        doctorName: 'Dr. Sarah Connor',
        facilityName: 'General Hospital',
        facilityLocation: '123 Main St, Anytown, USA',
        datetime: '2025-11-22T09:00:00',
        serviceCode: {
            code: 'E789',
            description: 'Physical Therapy',
        },
        waitlist: 5,
        status: 'upcoming',
    },
    {
        id: '6',
        doctorName: 'Dr. Robert Brown',
        facilityName: 'Cardio Center',
        facilityLocation: '321 Elm St, Anytown, USA',
        datetime: '2025-11-25T16:00:00',
        serviceCode: {
            code: 'F112',
            description: 'Cardiology Follow-up',
        },
        waitlist: 0,
        status: 'upcoming',
    },
    {
        id: '7',
        doctorName: 'Dr. John Doe',
        facilityName: 'City Clinic',
        facilityLocation: '456 Oak Ave, Anytown, USA',
        datetime: '2025-11-12T08:30:00',
        serviceCode: {
            code: 'G223',
            description: 'Blood Test',
        },
        waitlist: 0,
        status: 'cancelled',
    },
    {
        id: '8',
        doctorName: 'Dr. Jessica White',
        facilityName: 'Vision Care',
        facilityLocation: '654 Maple St, Anytown, USA',
        datetime: '2025-11-10T13:15:00',
        serviceCode: {
            code: 'H334',
            description: 'Eye Exam',
        },
        waitlist: 0,
        status: 'no show',
    },
];
