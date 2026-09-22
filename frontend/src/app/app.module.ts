import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';


import { AppComponent } from './app.component';
import { AvisosListaComponent } from './avisos-lista/avisos-lista.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { EntrarComponent } from './entrar/entrar.component';
import { HTTP_INTERCEPTORS, HttpClientModule } from '@angular/common/http';
import { AuthInterceptor } from './interceptores/auth.interceptor';
import { ErroresInterceptor } from './interceptores/errores.interceptor';
import { AvisoNuevoComponent } from './aviso-nuevo/aviso-nuevo.component';



@NgModule({
  declarations: [
    AppComponent,
    AvisosListaComponent,
    EntrarComponent,
    AvisoNuevoComponent
  ],
    imports: [
    BrowserModule,
    HttpClientModule,
    FormsModule,
    ReactiveFormsModule
  ],

    providers: [
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true },
    { provide: HTTP_INTERCEPTORS, useClass: ErroresInterceptor, multi: true }
  ],

  bootstrap: [AppComponent]
})
export class AppModule { }