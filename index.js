// Нейросеть контекстной обработки предложений
class ContextNN {
    constructor() {
        this.weights = {};
        this.noiseWords = new Set(['очень', 'просто', 'так', 'такой', 'этот', 'эта', 'это', 'вроде', 'как бы', 'наверное', 'действительно', 'буквально', 'фактически']);
        this.learningRate = 0.1;
    }
    
    tokenize(text) {
        return text.toLowerCase().match(/\b[\w']+\b/g) || [];
    }
    
    extractFeatures(tokens, idx) {
        const word = tokens[idx];
        const prev = idx > 0 ? tokens[idx-1] : null;
        const next = idx < tokens.length-1 ? tokens[idx+1] : null;
        return { word, prev, next };
    }
    
    computeContextScore(features) {
        let score = 0;
        if (features.prev && this.weights[`${features.prev}->${features.word}`]) score += this.weights[`${features.prev}->${features.word}`];
        if (features.next && this.weights[`${features.word}->${features.next}`]) score += this.weights[`${features.word}->${features.next}`];
        if (this.noiseWords.has(features.word)) score -= 0.5;
        return score;
    }
    
    filterSentence(tokens) {
        return tokens.filter((word, idx) => {
            const feat = this.extractFeatures(tokens, idx);
            return this.computeContextScore(feat) > -0.3;
        });
    }
    
    learn(sentence) {
        const tokens = this.tokenize(sentence);
        for (let i = 0; i < tokens.length - 1; i++) {
            const pair = `${tokens[i]}->${tokens[i+1]}`;
            this.weights[pair] = (this.weights[pair] || 0) + this.learningRate;
        }
        for (let i = 0; i < tokens.length; i++) {
            if (this.noiseWords.has(tokens[i])) {
                for (let j = -1; j <= 1; j++) {
                    if (tokens[i+j]) {
                        const pair = j === -1 ? `${tokens[i+j]}->${tokens[i]}` : `${tokens[i]}->${tokens[i+j]}`;
                        if (this.weights[pair]) this.weights[pair] -= this.learningRate * 0.5;
                    }
                }
            }
        }
    }
    
    toBinary(text) {
        let binary = '';
        for (let i = 0; i < text.length; i++) {
            binary += text.charCodeAt(i).toString(2).padStart(8, '0');
        }
        return binary;
    }
    
    process(sentence) {
        this.learn(sentence);
        const tokens = this.tokenize(sentence);
        const filtered = this.filterSentence(tokens);
        const cleaned = filtered.join(' ');
        const binary = this.toBinary(cleaned);
        return { original: sentence, cleaned, binary, contextWeights: {...this.weights} };
    }
}

// Экспорт (для Node.js) или глобальная переменная
if (typeof module !== 'undefined' && module.exports) module.exports = ContextNN;
