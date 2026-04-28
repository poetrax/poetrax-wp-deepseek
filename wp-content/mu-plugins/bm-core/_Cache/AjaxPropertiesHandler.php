<?php
namespace BM\Cache;

class AjaxPropertiesHandler {
    private PropertiesCacheManager $cacheManager;
    private string $nonceAction = 'properties_nonce';
    
    public function __construct(PropertiesCacheManager $cacheManager) {
        $this->cacheManager = $cacheManager;
        $this->registerHooks();
    }
    
    private function registerHooks(): void {
        add_action('wp_ajax_get_properties', [$this, 'handleGetProperties']);
        add_action('wp_ajax_nopriv_get_properties', [$this, 'handleGetProperties']);
        add_action('wp_ajax_refresh_properties_cache', [$this, 'handleRefreshCache']);
    }
    
    public function handleGetProperties(): void {
        try {
            $this->verifyNonce();
            
            $type = $this->sanitizeType($_POST['property_type'] ?? '');
            $forceRefresh = filter_var($_POST['force_refresh'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            $response = $this->cacheManager->getProperties($type, $forceRefresh);
            
            wp_send_json_success($response);
            
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error('Invalid property type');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
        
        wp_die();
    }
    
    public function handleRefreshCache(): void {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception('Unauthorized');
            }
            
            $this->verifyNonce();
            
            $type = $this->sanitizeType($_POST['property_type'] ?? null);
            $result = $this->cacheManager->invalidateCache($type);
            
            if ($type) {
                // Разогреваем кэш после очистки
                $this->cacheManager->warmupCache($type);
            }
            
            wp_send_json_success([
                'success' => $result,
                'message' => $type ? "Cache for $type refreshed" : "All cache refreshed"
            ]);
            
        } catch (\Exception $e) {
            $this->handleError($e);
        }
        
        wp_die();
    }
    
    private function verifyNonce(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', $this->nonceAction)) {
            throw new \Exception('Security verification failed');
        }
    }
    
    private function sanitizeType(?string $type): string {
        return sanitize_text_field($type ?? '');
    }
    
    private function handleError(\Exception $e): void {
        error_log('Properties Handler Error: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}