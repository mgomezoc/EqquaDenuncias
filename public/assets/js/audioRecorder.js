// audioRecorder.js

class AudioRecorder {
    constructor() {
        this.audioBlobs = [];
        this.mediaRecorder = null;
        this.streamBeingCaptured = null;
    }

    /** Start recording audio */
    start() {
        return new Promise((resolve, reject) => {
            // Check for mediaDevices and getUserMedia support
            if (!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
                return reject(new Error('getUserMedia not supported on this browser.'));
            }

            // Start capturing audio stream
            navigator.mediaDevices
                .getUserMedia({ audio: true })
                .then(stream => {
                    this.streamBeingCaptured = stream;

                    // Create MediaRecorder instance and start recording
                    this.mediaRecorder = new MediaRecorder(stream);
                    this.audioBlobs = [];

                    // Push Blob data to array when available
                    this.mediaRecorder.addEventListener('dataavailable', event => {
                        this.audioBlobs.push(event.data);
                    });

                    this.mediaRecorder.start();
                    resolve();
                })
                .catch(err => reject(err));
        });
    }

    /** Stop recording audio and return the audio blob */
    stop() {
        return new Promise((resolve, reject) => {
            if (!this.mediaRecorder) {
                return reject(new Error('No recording in progress.'));
            }

            // Stop the MediaRecorder and create the final Blob
            this.mediaRecorder.addEventListener('stop', () => {
                const audioBlob = new Blob(this.audioBlobs, { type: this.mediaRecorder.mimeType });
                this.stopStream();
                this.reset();
                resolve(audioBlob);
            });

            this.mediaRecorder.stop();
        });
    }

    /** Cancel the recording */
    cancel() {
        if (this.mediaRecorder) {
            this.mediaRecorder.stop();
            this.stopStream();
            this.reset();
        }
    }

    /** Stop the stream to release resources */
    stopStream() {
        if (this.streamBeingCaptured) {
            this.streamBeingCaptured.getTracks().forEach(track => track.stop());
        }
    }

    /** Reset all properties for next recording */
    reset() {
        this.mediaRecorder = null;
        this.streamBeingCaptured = null;
        this.audioBlobs = [];
    }
}

export default AudioRecorder;
