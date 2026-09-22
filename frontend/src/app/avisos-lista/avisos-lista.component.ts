import { Component, OnInit } from '@angular/core';

import { Aviso } from '../modelos/aviso';
import { AvisosService } from '../servicios/avisos.service';
import { HttpErrorResponse } from '@angular/common/http';
import { SesionService } from '../servicios/sesion.service';


@Component({
  selector: 'app-avisos-lista',
  templateUrl: './avisos-lista.component.html',
  styleUrls: ['./avisos-lista.component.css']
})
export class AvisosListaComponent implements OnInit {
  avisos: Aviso[] = [];
  cargando = true;
  error = '';
  mensaje = '';
  mensajeOk = false;


   constructor(private avisosService: AvisosService, public sesion: SesionService) { }

  ngOnInit(): void {
    this.cargar();
  }

  cargar(): void {
    this.cargando = true;
    this.avisosService.listar().subscribe({
      next: avisos => {
        this.avisos = avisos;
        this.cargando = false;
      },
      error: () => {
        
        this.error = 'No pude hablar con tu API. Revisa que composer run dev siga corriendo.';
        this.cargando = false;
        
      }

      
      
    });
    
  }

    borrar(aviso: Aviso): void {
    this.mensaje = '';
    this.avisosService.borrar(aviso.id).subscribe({
      next: () => {
        this.avisos = this.avisos.filter(a => a.id !== aviso.id);
        this.mensaje = `204 · borraste "${aviso.titulo}"`;
        this.mensajeOk = true;
      },
      error: (e: HttpErrorResponse) => {
        this.mensaje = e.status === 403
          ? '403 · ese aviso no es tuyo. Lo decidió tu PostPolicy, no Angular.'
          : `${e.status} · tu API no lo borró`;
        this.mensajeOk = false;
      }
    });
  }

}
