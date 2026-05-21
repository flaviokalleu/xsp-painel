<?php
function contar_clientes_is_trial_0() {
    $conexao = conectar_bd();

    if (!$conexao) {
        return [
            'title' => 'Erro!',
            'msg' => 'Não foi possível conectar ao banco de dados.',
            'icon' => 'error'
        ];
    }

    try {
        $query = "SELECT COUNT(*) AS total FROM clientes WHERE is_trial = 1";
        $stmt = $conexao->prepare($query);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return [
                'title' => 'Sucesso!',
                'msg' => 'Número de clientes com is_trial igual a 1: ' . $resultado['total'],
                'icon' => 'success',
                'total' => $resultado['total']
            ];
        } else {
            return [
                'title' => 'Erro!',
                'msg' => 'Não foi possível encontrar os dados.',
                'icon' => 'error'
            ];
        }
    } catch (PDOException $e) {
        return [
            'title' => 'Erro!',
            'msg' => 'Erro na consulta: ' . $e->getMessage(),
            'icon' => 'error'
        ];
    }
}
?>
