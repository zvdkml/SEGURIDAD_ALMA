<div class="wrap alma-security-wrap pr-4">
    <div class="flex justify-between items-center py-6">
        <h1 class="text-3xl font-bold text-gray-800">Historial de Escaneos</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Críticas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medias</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bajas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ( empty( $history_data ) ) : ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">No hay historial de escaneos.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $history_data as $scan ) : ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date( 'Y-m-d H:i:s', $scan['timestamp'] ); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold <?php echo $scan['score'] >= 80 ? 'text-green-600' : ( $scan['score'] >= 50 ? 'text-yellow-600' : 'text-red-600' ); ?>">
                                <?php echo $scan['score']; ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <span class="font-bold text-red-600"><?php echo isset($scan['counts']['critico']) ? $scan['counts']['critico'] : '0'; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <span class="font-bold text-yellow-600"><?php echo isset($scan['counts']['medio']) ? $scan['counts']['medio'] : '0'; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <span class="font-bold text-blue-600"><?php echo isset($scan['counts']['bajo']) ? $scan['counts']['bajo'] : '0'; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <button class="view-scan-detail p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all" data-index="<?php echo array_search( $scan, $history_data ); ?>" title="Ver detalles">
                                    <span class="dashicons dashicons-search"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
