const registro = new Map<string, Function>();

function Pieza(nombre: string) {
  return function (clase: Function): void {
    registro.set(nombre, clase);
  };
}

@Pieza('lista-de-avisos')
class ListaDeAvisos { }

@Pieza('formulario-de-entrar')
class FormularioDeEntrar { }

console.log('Piezas registradas:', [...registro.keys()]);
console.log('La clase detrás de lista-de-avisos:', registro.get('lista-de-avisos')?.name);
