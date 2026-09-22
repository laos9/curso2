interface Aviso {
  id: number;
  titulo: string;
  categoria?: { id: number; nombre: string };
  creado: string;
}

const avisos: Aviso[] = [
  { id: 1, titulo: 'Cambio de horario en barandilla', categoria: { id: 1, nombre: 'Aviso' }, creado: '2026-09-10T10:00:00-06:00' },
  { id: 2, titulo: 'Curso de primeros auxilios', creado: '2026-09-09T09:00:00-06:00' }
];

function vencimiento(fecha: string, dias: number): Date {
  const dia = new Date(fecha);
  dia.setDate(dia.getDate() + dias);
  return dia;
}

function titulares(lista: Aviso[]): string[] {
  return lista.map(aviso => aviso.titulo.toUpperCase());
}

function categorias(lista: Aviso[]): string[] {
  return lista.map(aviso => aviso.categoria?.nombre ?? 'Sin categoría');
}

console.log('Vence:', vencimiento(avisos[0].creado, 3).toISOString().slice(0, 10));
console.log('Titulares:', titulares(avisos));
console.log('Categorias:', categorias(avisos));

type Rol = 'admin' | 'editor' | 'lector';

interface Sesion {
  token: string;
  usuario: string;
  rol: Rol;
}

// La regla de create() de tu PostPolicy, del lado de la pantalla.
function puedeCrear(sesion: Sesion | null): boolean {
  if (sesion === null) {
    return false;
  }
  return sesion.rol === 'admin' || sesion.rol === 'editor';
}

const editor: Sesion = { token: 'abc', usuario: 'Editor de guardia', rol: 'editor' };
console.log('¿Puede crear el editor?', puedeCrear(editor));
console.log('¿Y sin sesión?', puedeCrear(null));

interface Respuesta<T> {
  data: T;
}

function sacar<T>(respuesta: Respuesta<T>): T {
  return respuesta.data;
}

const muchos: Respuesta<Aviso[]> = { data: avisos };
const uno: Respuesta<Aviso> = { data: avisos[0] };

console.log('Cuántos:', sacar(muchos).length);
console.log('Uno:', sacar(uno).titulo);

class AvisosRepositorio {
  private pedidas = 0;

  constructor(private base: string) { }

  async listar(): Promise<Aviso[]> {
    this.pedidas++;
    const respuesta = await fetch(`${this.base}/api/avisos`);
    // json() devuelve any: el "as" es una promesa a TypeScript, no una revision.
    const cuerpo = await respuesta.json() as Respuesta<Aviso[]>;
    return cuerpo.data;
  }

  get veces(): number {
    return this.pedidas;
  }
}

async function main(): Promise<void> {
  const repo = new AvisosRepositorio('http://127.0.0.1:8000');
  const deTuApi = await repo.listar();
  console.log('Desde tu API:', titulares(deTuApi));
  console.log('Sus categorías:', categorias(deTuApi));
  console.log('Peticiones hechas:', repo.veces);
}

main();
