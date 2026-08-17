/**
 * RNN-LSTM demand forecaster (pure JavaScript, no native deps).
 *
 * Architecture (per dissertation §2.27):
 *   Input Layer -> Hidden LSTM Layer(s) -> Dense Layer -> Forecast Output Layer
 *
 * Input features follow the dissertation's input variables (historical issuance
 * quantity + month/date of issuance). LSTM cell uses the three gates:
 * Input Gate, Forget Gate, Output Gate. Loss: MSE. Optimizer: Adam (gradient
 * clipping). Evaluation metrics: MAE, RMSE, MAPE.
 */

function sigmoid(x) {
  return 1 / (1 + Math.exp(-x));
}

function mulberry32(seed) {
  let a = seed >>> 0;
  return function () {
    a |= 0;
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

/** Normalize a sequence element: numbers are wrapped into a single-feature vector. */
function asVector(x) {
  return Array.isArray(x) ? x : [x];
}

export class LSTMForecaster {
  /**
   * @param {object} opts
   * @param {number} opts.inputSize   number of input features (I)
   * @param {number} opts.hiddenSize  number of hidden LSTM units (H)
   */
  constructor({ inputSize = 1, hiddenSize = 8, seed = 42 } = {}) {
    const H = hiddenSize;
    this.H = H;
    this.I = inputSize;

    const rand = mulberry32(seed);
    const bound = 1 / Math.sqrt(H);

    this.W = new Float64Array(4 * H * this.I); // input->gates  (rows [i f o g])
    this.U = new Float64Array(4 * H * H);      // hidden->gates
    this.b = new Float64Array(4 * H);          // biases
    this.wy = new Float64Array(H);             // hidden->output
    this.by = 0;
    this.lastY = 0;

    for (let i = 0; i < this.W.length; i++) this.W[i] = (rand() * 2 - 1) * bound;
    for (let i = 0; i < this.U.length; i++) this.U[i] = (rand() * 2 - 1) * bound;
    for (let i = 0; i < this.b.length; i++) this.b[i] = (rand() * 2 - 1) * bound;
    for (let i = 0; i < this.H; i++) this.wy[i] = (rand() * 2 - 1) * bound;
  }

  /**
   * Forward pass over a sequence of feature vectors (or scalars).
   * @param {Array} seq  length T
   * @returns {object} caches needed for BPTT + scalar prediction
   */
  forward(seq) {
    const H = this.H, I = this.I;
    const T = seq.length;
    const W = this.W, U = this.U, b = this.b, wy = this.wy;

    const h = new Array(T + 1);
    const c = new Array(T + 1);
    h[0] = new Float64Array(H);
    c[0] = new Float64Array(H);
    const gates = [];

    for (let t = 0; t < T; t++) {
      const x = asVector(seq[t]);
      const hPrev = h[t];
      const hCur = new Float64Array(H);
      const cCur = new Float64Array(H);
      const ai = new Float64Array(H);
      const af = new Float64Array(H);
      const ao = new Float64Array(H);
      const ag = new Float64Array(H);
      const gi = new Float64Array(H);
      const gf = new Float64Array(H);
      const go = new Float64Array(H);
      const gg = new Float64Array(H);

      for (let j = 0; j < H; j++) {
        let sumI = b[j], sumF = b[H + j], sumO = b[2 * H + j], sumG = b[3 * H + j];
        for (let k = 0; k < I; k++) {
          const xk = x[k];
          sumI += W[j * I + k] * xk;
          sumF += W[(H + j) * I + k] * xk;
          sumO += W[(2 * H + j) * I + k] * xk;
          sumG += W[(3 * H + j) * I + k] * xk;
        }
        for (let k = 0; k < H; k++) {
          const hk = hPrev[k];
          sumI += U[j * H + k] * hk;
          sumF += U[(H + j) * H + k] * hk;
          sumO += U[(2 * H + j) * H + k] * hk;
          sumG += U[(3 * H + j) * H + k] * hk;
        }
        const iv = sigmoid(sumI);
        const fv = sigmoid(sumF);
        const ov = sigmoid(sumO);
        const gv = Math.tanh(sumG);
        cCur[j] = fv * c[t][j] + iv * gv;
        hCur[j] = ov * Math.tanh(cCur[j]);
        ai[j] = sumI; af[j] = sumF; ao[j] = sumO; ag[j] = sumG;
        gi[j] = iv; gf[j] = fv; go[j] = ov; gg[j] = gv;
      }
      h[t + 1] = hCur;
      c[t + 1] = cCur;
      gates.push({ ai, af, ao, ag, gi, gf, go, gg, x });
    }

    const hT = h[T];
    let y = this.by;
    for (let j = 0; j < H; j++) y += wy[j] * hT[j];
    this.lastY = y;
    return { y, h, c, gates, hT };
  }

  /**
   * Backpropagation through time (single sample), accumulating gradients in place.
   */
  backward(seq, gates, h, c, yTrue) {
    const H = this.H, I = this.I;
    const T = seq.length;
    const W = this.W, U = this.U, wy = this.wy;
    const dW = new Float64Array(this.W.length);
    const dU = new Float64Array(this.U.length);
    const db = new Float64Array(this.b.length);
    const dwy = new Float64Array(H);

    const delta = this.lastY - yTrue;

    const dh = new Float64Array(H);
    for (let j = 0; j < H; j++) dh[j] = delta * wy[j];
    const dc = new Float64Array(H);

    for (let t = T - 1; t >= 0; t--) {
      const g = gates[t];
      const cCur = c[t + 1];
      const cPrev = c[t];
      const hPrev = h[t];
      const x = g.x;

      const dcTotal = new Float64Array(H);
      const tanhC = new Float64Array(H);
      for (let j = 0; j < H; j++) {
        tanhC[j] = Math.tanh(cCur[j]);
        dcTotal[j] = dc[j] + dh[j] * g.go[j] * (1 - tanhC[j] * tanhC[j]);
      }

      const dai = new Float64Array(H), daf = new Float64Array(H);
      const dao = new Float64Array(H), dag = new Float64Array(H);
      for (let j = 0; j < H; j++) {
        const di = dcTotal[j] * g.gg[j];
        const df = dcTotal[j] * cPrev[j];
        const dg = dcTotal[j] * g.gi[j];
        const dO = dh[j] * tanhC[j];
        dai[j] = di * g.gi[j] * (1 - g.gi[j]);
        daf[j] = df * g.gf[j] * (1 - g.gf[j]);
        dao[j] = dO * g.go[j] * (1 - g.go[j]);
        dag[j] = dg * (1 - g.gg[j] * g.gg[j]);
      }

      for (let j = 0; j < H; j++) {
        for (let k = 0; k < I; k++) {
          const xk = x[k];
          dW[j * I + k] += dai[j] * xk;
          dW[(H + j) * I + k] += daf[j] * xk;
          dW[(2 * H + j) * I + k] += dao[j] * xk;
          dW[(3 * H + j) * I + k] += dag[j] * xk;
        }
        db[j] += dai[j];
        db[H + j] += daf[j];
        db[2 * H + j] += dao[j];
        db[3 * H + j] += dag[j];
        for (let k = 0; k < H; k++) {
          const hk = hPrev[k];
          dU[j * H + k] += dai[j] * hk;
          dU[(H + j) * H + k] += daf[j] * hk;
          dU[(2 * H + j) * H + k] += dao[j] * hk;
          dU[(3 * H + j) * H + k] += dag[j] * hk;
        }
      }

      const dhPrev = new Float64Array(H);
      const dcPrev = new Float64Array(H);
      for (let j = 0; j < H; j++) {
        let acc = 0;
        for (let k = 0; k < H; k++) {
          acc += U[k * H + j] * dai[k] + U[(H + k) * H + j] * daf[k];
          acc += U[(2 * H + k) * H + j] * dao[k] + U[(3 * H + k) * H + j] * dag[k];
        }
        dhPrev[j] = acc;
        dcPrev[j] = dcTotal[j] * g.gf[j];
      }
      dh.set(dhPrev);
      dc.set(dcPrev);
    }
    return { dW, dU, db, dwy, dby: delta };
  }

  /**
   * Train on (sequence, target) pairs.
   * @param {Array[]} samples
   * @param {number[]} targets
   */
  fit(samples, targets, { epochs = 300, lr = 0.01, clip = 5 } = {}) {
    const P = this.W.length + this.U.length + this.b.length + this.H + 1;
    const params = concatParams(this);
    const m = new Float64Array(P);
    const v = new Float64Array(P);

    const beta1 = 0.9, beta2 = 0.999, eps = 1e-8;
    let globalStep = 0;

    for (let epoch = 0; epoch < epochs; epoch++) {
      let loss = 0;
      const gradAcc = {
        dW: new Float64Array(this.W.length),
        dU: new Float64Array(this.U.length),
        db: new Float64Array(this.b.length),
        dwy: new Float64Array(this.H),
        dby: 0,
      };
      for (let s = 0; s < samples.length; s++) {
        const seq = samples[s];
        const { y, h, c, gates } = this.forward(seq);
        const tgt = targets[s];
        loss += 0.5 * (y - tgt) * (y - tgt);
        const g = this.backward(seq, gates, h, c, tgt);
        addGrad(gradAcc, g);
      }

      const gradFlat = flattenGrads(gradAcc);
      let norm = normOf(gradFlat);
      if (norm > clip) {
        const s = clip / norm;
        for (let i = 0; i < gradFlat.length; i++) gradFlat[i] *= s;
      }

      globalStep++;
      const bc1 = 1 - Math.pow(beta1, globalStep);
      const bc2 = 1 - Math.pow(beta2, globalStep);
      for (let i = 0; i < P; i++) {
        m[i] = beta1 * m[i] + (1 - beta1) * gradFlat[i];
        v[i] = beta2 * v[i] + (1 - beta2) * gradFlat[i] * gradFlat[i];
        const mhat = m[i] / bc1;
        const vhat = v[i] / bc2;
        params[i] -= (lr * mhat) / (Math.sqrt(vhat) + eps);
      }
      assignParams(this, params);
      this.lastLoss = loss / Math.max(samples.length, 1);
    }
    return this.lastLoss;
  }

  /** Single-step forecast given a trailing sequence of inputs. */
  predict(seq) {
    const { y } = this.forward(seq);
    return y;
  }

  /**
   * Recursive multi-step forecast for scalar series (single-input networks only).
   * `nextInputFn(predictedScalar, stepIndex)` may build the next input vector.
   */
  predictMany(seq, steps, nextInputFn = null) {
    const out = [];
    let cur = seq.slice();
    for (let s = 0; s < steps; s++) {
      const y = this.predict(cur);
      out.push(y);
      if (nextInputFn) {
        const feed = nextInputFn(y, s);
        cur = cur.slice(1).concat([feed]);
      } else {
        cur = cur.slice(1).concat(y);
      }
    }
    return out;
  }
}

function concatParams(model) {
  return new Float64Array([
    ...Array.from(model.W),
    ...Array.from(model.U),
    ...Array.from(model.b),
    ...Array.from(model.wy),
    model.by,
  ]);
}

function assignParams(model, params) {
  let p = 0;
  for (let i = 0; i < model.W.length; i++) model.W[i] = params[p++];
  for (let i = 0; i < model.U.length; i++) model.U[i] = params[p++];
  for (let i = 0; i < model.b.length; i++) model.b[i] = params[p++];
  for (let i = 0; i < model.H; i++) model.wy[i] = params[p++];
  model.by = params[p];
}

function addGrad(acc, g) {
  for (let i = 0; i < acc.dW.length; i++) acc.dW[i] += g.dW[i];
  for (let i = 0; i < acc.dU.length; i++) acc.dU[i] += g.dU[i];
  for (let i = 0; i < acc.db.length; i++) acc.db[i] += g.db[i];
  for (let i = 0; i < acc.dwy.length; i++) acc.dwy[i] += g.dwy[i];
  acc.dby += g.dby;
}

function flattenGrads(g) {
  const P = g.dW.length + g.dU.length + g.db.length + g.dwy.length + 1;
  const flat = new Float64Array(P);
  let pi = 0;
  flat.set(g.dW, pi); pi += g.dW.length;
  flat.set(g.dU, pi); pi += g.dU.length;
  flat.set(g.db, pi); pi += g.db.length;
  flat.set(g.dwy, pi); pi += g.dwy.length;
  flat[pi] = g.dby;
  return flat;
}

function normOf(arr) {
  let s = 0;
  for (let i = 0; i < arr.length; i++) s += arr[i] * arr[i];
  return Math.sqrt(s);
}

/** Build windowed training samples from a time series (works for scalars or vectors). */
export function makeSequences(series, seqLen) {
  const samples = [];
  const targets = [];
  for (let i = 0; i + seqLen < series.length; i++) {
    samples.push(series.slice(i, i + seqLen));
    targets.push(series[i + seqLen]);
  }
  return { samples, targets };
}