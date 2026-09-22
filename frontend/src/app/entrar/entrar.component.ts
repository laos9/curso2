import { Component } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';

import { SesionService } from '../servicios/sesion.service';

@Component({
  selector: 'app-entrar',
  templateUrl: './entrar.component.html',
  styleUrls: ['./entrar.component.css']
})
export class EntrarComponent {
  email = 'editor@blog.test';
  password = '';
  error = '';
  enviando = false;
  quienSoy = '';
  quienSoyOk = false;

  constructor(public sesion: SesionService) { }

  entrar(): void {
    this.error = '';
    this.enviando = true;
    this.sesion.entrar(this.email, this.password).subscribe({
      next: () => {
        this.enviando = false;
        this.password = '';
      },
      error: (e: HttpErrorResponse) => {
        this.enviando = false;
        this.error = e.error?.message ?? `Tu API respondió ${e.status}`;
      }
    });
  }

  preguntarQuienSoy(): void {
    this.sesion.yo().subscribe({
      next: yo => {
        this.quienSoy = `200 · tu API te reconoce: ${yo.nombre} (${yo.rol})`;
        this.quienSoyOk = true;
      },
      error: (e: HttpErrorResponse) => {
        this.quienSoy = `${e.status} · tu API no sabe quién eres`;
        this.quienSoyOk = false;
      }
    });
  }

  salir(): void {
    this.sesion.salir();
    this.quienSoy = '';
  }
}
