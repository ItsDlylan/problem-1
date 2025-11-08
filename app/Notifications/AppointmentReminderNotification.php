<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for appointment reminders.
 * Sends an email reminder to patients 24 hours before their scheduled appointment.
 * Includes appointment details like date, time, facility, doctor, and service.
 */
final class AppointmentReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Appointment $appointment,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     * Formats the email with appointment details and uses patient's preferred language if available.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $patient = $appointment->patient;
        $facility = $appointment->facility;
        $doctor = $appointment->doctor;
        $serviceOffering = $appointment->serviceOffering;

        // Format appointment date and time
        $appointmentDate = $appointment->start_at->format('l, F j, Y');
        $appointmentTime = $appointment->start_at->format('g:i A');
        $appointmentEndTime = $appointment->end_at->format('g:i A');

        // Build the mail message
        $message = (new MailMessage)
            ->subject('Appointment Reminder: ' . $appointmentDate)
            ->greeting('Hello ' . $patient->first_name . ',')
            ->line('This is a reminder that you have an upcoming appointment.')
            ->line('**Appointment Details:**')
            ->line('**Date:** ' . $appointmentDate)
            ->line('**Time:** ' . $appointmentTime . ' - ' . $appointmentEndTime);

        // Add facility information if available
        if ($facility) {
            $message->line('**Facility:** ' . $facility->name);
            if ($facility->address) {
                $message->line('**Address:** ' . $facility->address);
            }
            if ($facility->phone) {
                $message->line('**Phone:** ' . $facility->phone);
            }
        }

        // Add doctor information if available
        if ($doctor) {
            $doctorName = $doctor->display_name ?? ($doctor->first_name . ' ' . $doctor->last_name);
            $message->line('**Doctor:** ' . $doctorName);
        }

        // Add service information if available
        if ($serviceOffering && $serviceOffering->service) {
            $message->line('**Service:** ' . $serviceOffering->service->name);
        }

        // Add notes if available
        if ($appointment->notes) {
            $message->line('**Notes:** ' . $appointment->notes);
        }

        // Add closing message
        $message->line('Please arrive 10-15 minutes early for your appointment.')
            ->line('If you need to reschedule or cancel, please contact the facility as soon as possible.')
            ->salutation('Thank you!');

        return $message;
    }
}

