import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { NonNullableFormBuilder, Validators } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';

import { Aviso } from '../modelos/aviso';
import { Categoria } from '../modelos/categoria';
import { AvisosService } from '../servicios/avisos.service';
import { CategoriasService } from '../servicios/categorias.service';
import { SesionService } from '../servicios/sesion.service';

@Component({
  selector: 'app-aviso-nuevo',
  templateUrl: './aviso-nuevo.component.html',
  styleUrls: ['./aviso-nuevo.component.css']
})
export class AvisoNuevoComponent implements OnInit {
  @Output() creado = new EventEmitter<Aviso>();

  // Las mismas reglas que tu validate() de Laravel, del lado de la pantalla.
  form = this.fb.group({
    titulo: ['', [Validators.required, Validators.maxLength(120)]],
    contenido: ['', Validators.required],
    categoria_id: [null as number | null, Validators.required]
  });

  categorias: Categoria[] = [];
  errores: Record<string, string[]> = {};
  mensaje = '';
  enviando = false;

  constructor(
    private fb: NonNullableFormBuilder,
    private avisosService: AvisosService,
    private categoriasService: CategoriasService,
    public sesion: SesionService
  ) { }

  ngOnInit(): void {
    this.categoriasService.listar().subscribe(categorias => this.categorias = categorias);
  }

  guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.errores = {};
    this.mensaje = '';
    this.enviando = true;
    this.avisosService.crear(this.form.getRawValue()).subscribe({
      next: creado => {
        this.enviando = false;
        this.mensaje = `201 · se creó "${creado.titulo}"`;
        this.form.reset();
        this.creado.emit(creado);
      },
      error: (e: HttpErrorResponse) => {
        this.enviando = false;
        if (e.status === 422) {
          this.errores = e.error.errors;
        } else {
          this.mensaje = `${e.status} · tu API no lo creó`;
        }
      }
    });
  }
}
