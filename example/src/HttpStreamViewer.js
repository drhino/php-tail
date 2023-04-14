import { HttpStream } from './HttpStream.js';

let previous;

/**
 * Example.
 */
export default class HttpStreamViewer extends HttpStream
{
    #viewer;
    #buffers = ['', '', ''];

    constructor(url) {
        super();
        this.#viewer = document.getElementById('viewer');
        this.stream(url);
    }

    onerror(error) {
        console.error('MyError:', error);
    }

    onbuffer(buffer) {
        buffer = String.fromCharCode(...buffer);

        this.#buffers.shift();
        this.#buffers.push(buffer);
        this.#viewer.innerText = this.#buffers.join('');

        // Prints each line to the console
        for (const line of buffer.trim().split(/\r?\n/)) {
            let [ time, counter ] = line.split('---');
            counter = parseInt(counter, 10);
            //console.log(time, counter);

            if (previous) {
                if (previous === 9) {
                    previous = 0;
                }

                if ((previous + 1) !== counter) {
                    console.error('Missed one!', line);
                }
            }

            previous = counter;
        }
    }
}
