import { Injectable } from '@angular/core';
import {
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest
} from '@angular/common/http';
import { Observable, catchError, throwError } from 'rxjs';

import { SesionService } from '../servicios/sesion.service';

@Injectable()
export class ErroresInterceptor implements HttpInterceptor {

  constructor(private sesion: SesionService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    return next.handle(request).pipe(
      catchError((error: HttpErrorResponse) => {
        if (error.status === 401 && this.sesion.token) {
          this.sesion.olvidar();
        }
        return throwError(() => error);
      })
    );
  }
}
