import { AxiosError } from 'axios';
import toast from 'react-hot-toast';

/**
 * Interface for API error response from the backend
 */
interface ApiErrorResponse {
  success: boolean;
  error_code: number;
  message: string;
  errors?: Record<string, string[]>;
}

/**
 * Extract validation errors from API response and format them
 * @param error - The error object from axios
 * @returns Object containing formatted error messages
 */
export const extractValidationErrors = (error: unknown): {
  toastMessage: string;
  fieldErrors: Record<string, string[]>;
  hasErrors: boolean;
} => {
  // Default return object
  const result = {
    toastMessage: 'An unexpected error occurred',
    fieldErrors: {} as Record<string, string[]>,
    hasErrors: false
  };

  if (!(error instanceof Error)) {
    return result;
  }

  // Handle Axios errors
  if ('isAxiosError' in error && error.isAxiosError) {
    const axiosError = error as AxiosError<ApiErrorResponse>;
    
    // Check if it's a validation error (422)
    if (axiosError.response?.status === 422) {
      const errorData = axiosError.response.data;
      
      // Extract field errors if they exist
      if (errorData.errors && Object.keys(errorData.errors).length > 0) {
        result.fieldErrors = errorData.errors;
        result.hasErrors = true;
        
        // Create a summary message for toast notification
        const errorMessages: string[] = [];
        
        // Extract all error messages and flatten them
        Object.entries(errorData.errors).forEach(([, messages]) => {
          if (Array.isArray(messages)) {
            // We don't need to format the field name for the toast message
            // but we keep the field for context in case we need it later
            
            // Add each message with the field name
            messages.forEach(message => {
              errorMessages.push(message);
            });
          }
        });
        
        // Create a concise toast message
        if (errorMessages.length > 0) {
          result.toastMessage = `Validation error: ${errorMessages[0]}${
            errorMessages.length > 1 ? ` (+${errorMessages.length - 1} more)` : ''
          }`;
        } else {
          result.toastMessage = 'Validation failed. Please check your input.';
        }
      } else if (errorData.message) {
        // If there are no field errors but there is a message
        result.toastMessage = `Validation error: ${errorData.message}`;
        result.hasErrors = true;
      }
    } else if (axiosError.response?.data?.message) {
      // Handle other error types with messages
      result.toastMessage = axiosError.response.data.message;
      result.hasErrors = true;
    } else if (axiosError.message) {
      // Use the axios error message as fallback
      result.toastMessage = axiosError.message;
      result.hasErrors = true;
    }
  } else {
    // Handle non-Axios errors
    result.toastMessage = error.message || 'An unexpected error occurred';
    result.hasErrors = true;
  }

  return result;
};

/**
 * Display validation errors as toast notifications
 * @param error - The error object from axios
 */
export const showValidationErrorToast = (error: unknown): void => {
  const { toastMessage } = extractValidationErrors(error);
  toast.error(toastMessage);
};

/**
 * Format all validation errors into a list for display in UI
 * @param error - The error object from axios
 * @returns Array of formatted error messages
 */
export const formatValidationErrorsList = (error: unknown): string[] => {
  const { fieldErrors } = extractValidationErrors(error);
  const formattedErrors: string[] = [];
  
  Object.entries(fieldErrors).forEach(([, messages]) => {
    if (Array.isArray(messages)) {
      messages.forEach(message => {
        formattedErrors.push(message);
      });
    }
  });
  
  return formattedErrors;
};
