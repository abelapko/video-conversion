# Microservice for MP4 to AVI Conversion

## Project Goal
To practice the theory and skills of working with RabbitMQ by creating a system that utilizes queues.

## Project Idea
A microservice for converting MP4 video files to AVI format using RabbitMQ queues to manage tasks.

## How it will work
1. The user uploads a file to the server.
2. The file is uploaded to the cloud, and a conversion task is placed in the RabbitMQ queue.
3. A conversion worker retrieves the task from the queue and performs the video conversion.
4. The converted video is uploaded to the cloud.
5. The conversion task is completed, and the status is updated.

## Tech Stack
- RabbitMQ for queue processing
- PHP for server-side logic
- Cloud storage for file uploads and storage
- Microservices for task management and conversion

## Installation and Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/abelapko/video-conversion
   ```
todo...