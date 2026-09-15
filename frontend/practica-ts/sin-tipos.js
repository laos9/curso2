// Ejercicio de TypeScript, paso 1: JavaScript sin tipos.
// Correlo tal cual, desde frontend/:
//
//   node practica-ts/sin-tipos.js
//
// Tiene tres errores a proposito. Fijate en cuales te avisa JavaScript,
// cuando te avisa, y cual no te avisa nunca.

const avisos = [
  { id: 1, titulo: 'Cambio de horario en barandilla', categoria: { id: 1, nombre: 'Aviso' }, creado: '2026-09-10T10:00:00-06:00' },
  { id: 2, titulo: 'Curso de primeros auxilios', creado: '2026-09-09T09:00:00-06:00' }
];

// La fecha en que vence un aviso: su fecha de creacion mas unos dias.
function vencimiento(fecha, dias) {
  return fecha + dias;
}

// Los titulos, en mayusculas.
function titulares(lista) {
  return lista.map(aviso => aviso.title.toUpperCase());
}

// El nombre de la categoria de cada aviso.
function categorias(lista) {
  return lista.map(aviso => aviso.categoria.nombre);
}

console.log('Vence:', vencimiento(avisos[0].creado, 3));
console.log('Titulares:', titulares(avisos));
console.log('Categorias:', categorias(avisos));
