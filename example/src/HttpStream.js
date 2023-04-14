/**
 * Resolves after the number of seconds defined in the `Retry-After` header.
 * With a minimum and default of 1 second and a maximum of 30 seconds.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Retry-After
 *
 * @param {Response} response
 *
 * @returns {Promise<void>}
 */
const retryAfter = response => new Promise(resolve => {
    let seconds;

    if (response) {
        const headerValue = response.headers.get('Retry-After');

        if (/^[0-9]*$/.test(headerValue)) {
            // <delay-seconds>
            seconds = parseInt(headerValue, 10);
        } else {
            // <http-date>
            seconds = new Date(headerValue).valueOf();
            seconds = Math.floor((seconds - Date.now()) / 1000);
        }
    }

    if (isNaN(seconds) || seconds < 1) {
        seconds = 1;
    } else if (seconds > 30) {
        seconds = 30;
    }

    setTimeout(resolve, seconds * 1000);
});

/**
 * Reads a HTTP stream and process data as it comes in.
 */
class HttpStream
{
    /** private @var string|URL|Request */
    #fetchResource; // a {string}, {Request} or any {object} with a stringifier

    /** private @var ?AbortController */
    #abortController;

    /** private @var ?boolean */
    #closed;

    /** public @var ?callable */
    // onerror;

    /** public @var ?callable */
    // onbuffer;

    /**
     * Opens the stream and executes `onbuffer()`.
     *
     * @param {string} resource The URL to the output stream.
     * @param {object} options The fetch API options to use.
     *
     * @returns {Promise<void>}
     */
    async stream(resource, options) {
        if (this.#abortController) {
            throw new Error('Stream already opened');
        }

        this.#fetchResource = resource;
        // ... @TODO: validate `this.#fetchResource`
        // ... @TODO: validate `options`

        // Retry until closed by the user
        while (!this.#closed) {
            this.#abortController = new AbortController();

            let response;

            try {
                // Throws {AbortError} when the user cancels the request
                response = await fetch(this.#fetchResource, {
                    ...options,
                    signal: this.#abortController.signal,
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw response;
                }

                const reader = response.body.getReader();

                // Reads the stream until it is closed by the server
                while (true) {
                    const { done, value } = await reader.read();

                    if (done) {
                        throw { 
                            name: 'ClosedByServerError',
                            message: 'The connection was closed by the server',
                        };
                    }

                    await this.onbuffer(value);
                }
            } catch (error) {
                if (this.onerror) {
                    try {
                        this.onerror(error);
                    } catch (e) {
                        console.error(e);
                        break;
                    }
                }
            }

            this.#abortController.abort();

            if (response?.headers.has('Location')) {
                // ... @TODO: validate `this.#fetchResource`
                this.#fetchResource = response.headers.get('Location');
            }

            await retryAfter(response);
        }

        this.close();
    }

    /**
     * Closes the stream.
     *
     * @returns undefined
     */
    close() {
        this.#closed = true;
        this.#abortController.abort();
    }
}

export { HttpStream, retryAfter };
