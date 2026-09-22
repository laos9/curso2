import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';


import { Aviso, NuevoAviso } from '../modelos/aviso';


@Injectable({
  providedIn: 'root'
})
export class AvisosService {

  constructor(private http: HttpClient) { }

  listar(): Observable<Aviso[]> {
    return this.http.get<{ data: Aviso[] }>('/api/avisos').pipe(
      map(respuesta => respuesta.data)
    );
  }
     crear(aviso: NuevoAviso): Observable<Aviso> {
    return this.http.post<{ data: Aviso }>('/api/avisos', aviso).pipe(
      map(respuesta => respuesta.data)
    );
  }

  borrar(id: number): Observable<void> {
    return this.http.delete<void>(`/api/avisos/${id}`);
  }

}
