<?php
    if(!empty($alertas)):
        foreach($alertas as $tipo => $mensajes):
            foreach($mensajes as $mensaje):
?>
    <div class="alerta <?php echo s($tipo); ?>">
        <i class="fa-solid <?php echo ($tipo === 'exito') ? 'fa-circle-check' : (($tipo === 'alerta') ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?>"></i>
        <span><?php echo s($mensaje); ?></span>
    </div>
<?php
            endforeach;
        endforeach;
    endif;
?>
