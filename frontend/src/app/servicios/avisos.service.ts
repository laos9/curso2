import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';


import { Aviso, NuevoAviso } from '../modelos/aviso';


@Injectable({
  providedIn: 'root'
})
export class AvisosService {

  constructor(private http: HttpClient) { }

  // DRF pagina la lista: los avisos vienen en results, no en data.
  // Y las rutas llevan barra final, o la redireccion se come el cuerpo del POST.
  listar(): Observable<Aviso[]> {
    return this.http.get<{ results: Aviso[] }>('/api/avisos/').pipe(
      map(respuesta => respuesta.results)
    );
  }

  // DRF devuelve el aviso creado tal cual, sin envolverlo en data.
  crear(aviso: NuevoAviso): Observable<Aviso> {
    return this.http.post<Aviso>('/api/avisos/', aviso);
  }

  borrar(id: number): Observable<void> {
    return this.http.delete<void>(`/api/avisos/${id}/`);
  }

}
