# Use the official PHP image
FROM php:8.2-cli

# Set the working directory
WORKDIR /app

# Copy all files into the container
COPY . .

# Expose port 3000
EXPOSE 3000

# Start PHP's built-in server
CMD ["php", "-S", "0.0.0.0:3000"]
