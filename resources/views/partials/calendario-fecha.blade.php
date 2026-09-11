{{--
    Calendario de selección de fecha. Reemplaza al <input type="date">: los
    días bloqueados (ya pasaron o no tienen disponibilidad habilitada por
    administración para ese día de la semana) no se pueden clickear.

    Requiere que el componente que lo incluye use el trait
    SeleccionaFechaConCalendario (expone mesAnterior/mesSiguiente,
    seleccionarFecha, diasCalendario() y nombreMesCalendario()), y tenga la
    propiedad pública $fecha.
--}}
<div class="ies-calendario @error('fecha') is-invalid @enderror">
    <div class="ies-calendario-header">
        <button type="button" class="ies-calendario-nav" wire:click="mesAnterior" aria-label="Mes anterior">
            &laquo;
        </button>
        <span class="ies-calendario-mes">{{ $this->nombreMesCalendario() }}</span>
        <button type="button" class="ies-calendario-nav" wire:click="mesSiguiente" aria-label="Mes siguiente">
            &raquo;
        </button>
    </div>

    <div class="ies-calendario-semana">
        <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sa</span><span>Do</span>
    </div>

    <div class="ies-calendario-grilla" wire:loading.class="ies-calendario-cargando" wire:target="mesAnterior,mesSiguiente,seleccionarFecha">
        @foreach ($this->diasCalendario() as $d)
            <button
                type="button"
                wire:key="dia-{{ $d['fecha'] }}"
                wire:click="seleccionarFecha('{{ $d['fecha'] }}')"
                @disabled($d['deshabilitado'])
                class="ies-calendario-dia
                    @if(!$d['delMes']) ies-calendario-dia--fuera @endif
                    @if($d['esHoy']) ies-calendario-dia--hoy @endif
                    @if($d['esSeleccionado']) ies-calendario-dia--seleccionado @endif"
            >
                {{ $d['dia'] }}
            </button>
        @endforeach
    </div>

    <div class="ies-calendario-leyenda">
        <span><i class="ies-calendario-dot ies-calendario-dot--disponible"></i> Disponible</span>
        <span><i class="ies-calendario-dot ies-calendario-dot--bloqueado"></i> No habilitado</span>
    </div>
</div>
@error('fecha') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
