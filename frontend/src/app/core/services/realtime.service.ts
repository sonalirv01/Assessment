import { Injectable, OnDestroy } from '@angular/core';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { MetalType } from '../models/metal-type.model';

// laravel-echo expects Pusher on window when using the pusher broadcaster,
// which is also what a Reverb server speaks over the wire.
(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;

/**
 * One shared WebSocket connection to the Reverb server, for the app's only
 * real-time feed today: live metal price updates. When an admin changes a
 * rate, every open storefront/admin tab hears about it immediately instead
 * of waiting for a reload or a poll.
 */
@Injectable({ providedIn: 'root' })
export class RealtimeService implements OnDestroy {
  private echo = new Echo(
    environment.reverb.scheme === 'https'
      ? {
          broadcaster: 'reverb',
          key: environment.reverb.key,
          wsHost: environment.reverb.host,
          wssPort: environment.reverb.port,
          forceTLS: true,
          enabledTransports: ['wss'],
        }
      : {
          // Only enable the plain 'ws' transport here — with 'wss' also
          // enabled, pusher-js retries over wss on any ws failure, which
          // then gets blocked by the CSP connect-src (only ws:// is
          // allowed for a non-TLS Reverb host) and spams the console.
          broadcaster: 'reverb',
          key: environment.reverb.key,
          wsHost: environment.reverb.host,
          wsPort: environment.reverb.port,
          forceTLS: false,
          enabledTransports: ['ws'],
        }
  );

  /** Emits every time an admin saves a new rate for any metal type. */
  onMetalPriceUpdated(): Observable<MetalType> {
    return new Observable<MetalType>((subscriber) => {
      const channel = this.echo.channel('metal-prices');
      channel.listen('.metal-price.updated', (payload: MetalType) => subscriber.next(payload));

      return () => this.echo.leaveChannel('metal-prices');
    });
  }

  ngOnDestroy(): void {
    this.echo.disconnect();
  }
}
