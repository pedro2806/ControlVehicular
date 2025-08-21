<?php
header('Content-Type: application/json');
include 'conn.php';

$accion = $_POST["accion"];

$noEmpleado = $_POST["noEmpleado"];
$correo = $_POST["correo"];
$id_usuario = $_POST["id_usuario"];

//Validar Sistemas x Usuario
    if($accion == "ValidarOpciones"){
        $sql = "SELECT * FROM webmess_rrhh.accesos WHERE noEmpleado = $noEmpleado";

        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $info= [];
            while ($row = $result->fetch_assoc()) {
                $info[] = $row;
            }
            echo json_encode($info);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontraron coincidencias.']);
        }
    }

//MODIFICAR Usuario 
    if($accion == "getInfo") {
        
        $sql = "SELECT TIMESTAMPDIFF(YEAR, u.fechaIngreso, CURDATE()) AS antiguedad, d.departamento, j.nombre AS jefe,
                COALESCE((
                    SELECT dv.dias 
                    FROM webmess_rrhh.diasvacaciones dv 
                    WHERE dv.anio = TIMESTAMPDIFF(YEAR, u.fechaIngreso, CURDATE())
                    LIMIT 1
                ), 0) AS diasdisponibles,
                u.fechaIngreso,
                IFNULL(SUM(
                    CASE 
                        WHEN s.estatus = 2 AND s.autorizaRH = 2 AND s.tipo = 1 
                        THEN s.dias ELSE 0 
                    END
                ), 0) AS diasSol
                FROM usuarios u
                INNER JOIN usuarios j ON u.jefe = j.noEmpleado
                INNER JOIN webmess_rrhh.departamento d ON u.departamento = d.id
                LEFT JOIN webmess_rrhh.solicitudes s 
                    ON u.noEmpleado = s.empleado
                    AND s.fesolicitud BETWEEN 
                            (CASE
                                WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(u.fechaIngreso)) > CURDATE()
                                THEN MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(u.fechaIngreso))
                                ELSE MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(u.fechaIngreso))
                            END)
                        AND
                            (CASE
                                WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(u.fechaIngreso)) > CURDATE()
                                THEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(u.fechaIngreso))
                                ELSE MAKEDATE(YEAR(CURDATE()) + 1, DAYOFYEAR(u.fechaIngreso))
                            END)
                WHERE u.noEmpleado = $noEmpleado";

        $result = $conn->query($sql);
        //echo $sql;
        if ($result && $result->num_rows > 0) {
            $info= [];
            while ($row = $result->fetch_assoc()) {
                $info[] = $row;
            }
            echo json_encode(['status' => 'success', 'info' => $info]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontraron actividades planeadas o error en la consulta.']);
        }
    }

// Obtener id_usuario y placa
    if ($accion == 'getPlaca') {
        $noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';

        if (empty($noEmpleado)) {
            echo json_encode(['success' => false, 'message' => 'noEmpleado no recibido.']);
            exit;
        }

        // 1. Obtener id_usuario desde la tabla usuarios
        $sqlUsuario = "SELECT id_usuario FROM usuarios WHERE noEmpleado = $noEmpleado";
        $resultUsuario = $conn->query($sqlUsuario);

        if ($resultUsuario && $resultUsuario->num_rows > 0) {
            $rowUsuario = $resultUsuario->fetch_assoc();
            $id_usuario = $rowUsuario['id_usuario'];

            // 2. Obtener placa desde la tabla inventario usando id_usuario
            $sqlPlaca = "SELECT placa FROM inventario WHERE id_usuario = $id_usuario ORDER BY id_vehiculo DESC LIMIT 1";
            $resultPlaca = $conn->query($sqlPlaca);

            if ($resultPlaca && $resultPlaca->num_rows > 0) {
                $rowPlaca = $resultPlaca->fetch_assoc();
                echo json_encode(['success' => true, 'placa' => $rowPlaca['placa']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró placa para este usuario.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró id_usuario para este noEmpleado.']);
        }
    }
?>