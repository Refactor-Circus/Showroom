import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, O as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { t as c } from "./ChannelFields-cGE2z5za.js";
import { c as l, h as u, s as d, u as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as p, navigateTo as m } from "@refactor-circus/atrium";
import { UButton as h, UCard as ee, UFormField as te, UInput as ne } from "@refactor-circus/atrium/ui";
import { computed as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, isRef as C, openBlock as w, ref as T, toDisplayString as E, unref as D, watch as O, withCtx as k, withModifiers as A } from "vue";
import { useI18n as j } from "vue-i18n";
import { useRoute as M } from "vue-router";
// src/pages/channels/[channel].vue?vue&type=script&setup=true&lang.ts
var N = {
	class: "space-y-4",
	"data-testid": "showroom-channel"
}, P = {
	key: 0,
	class: "space-y-5"
}, F = { class: "flex flex-wrap items-start justify-between gap-3" }, I = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-channel-title"
}, L = { class: "font-mono text-sm text-muted" }, R = { class: "font-semibold" }, z = { class: "text-sm text-muted" }, B = /* @__PURE__ */ S({
	__name: "[channel]",
	setup(S) {
		let { t: B, locale: V } = j(), H = M(), U = a(), W = g(() => String(H.params.channel)), { client: G, data: K, pending: q, failed: J, status: Y, reload: X } = s(async (e) => r(await e.GET("/channels/{channel}", { params: { path: { channel: W.value } } })).data, W), re = n(async (e) => r(await e.GET("/locales", { params: { query: { per_page: 100 } } }))), ie = n(u), Z = g(() => K.value), Q = T(""), $ = T(l());
		O(K, () => {
			Z.value && (Q.value = Z.value.labels[V.value] ?? "", $.value = l(Z.value));
		}, { immediate: !0 });
		async function ae() {
			let e = Z.value;
			if (!G || !e) return;
			let t = {
				labels: f(e.labels, V.value, Q.value),
				...d($.value)
			};
			await U.run(async () => r(await G.PATCH("/channels/{channel}", {
				params: { path: { channel: W.value } },
				body: i(t)
			}))) && await X();
		}
		async function oe() {
			G && window.confirm(B("showroom.structure.channels.confirm_delete")) && await U.run(async () => r(await G.DELETE("/channels/{channel}", { params: { path: { channel: W.value } } }))) && await m("/showroom/channels");
		}
		return (n, r) => {
			let i = h, a = ne, s = te, l = ee, u = p;
			return w(), v("div", N, [x(u, {
				"screen-key": "showroom.channel",
				record: D(W)
			}, {
				default: k(() => [x(t, {
					ready: !D(q) && D(Z) !== null,
					failed: D(J),
					status: D(Y)
				}, {
					default: k(() => [D(Z) ? (w(), v("div", P, [
						y("div", F, [y("div", null, [y("h1", I, E(D(o)(D(Z).labels, D(V), D(Z).code)), 1), y("p", L, E(D(Z).code), 1)]), x(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: D(U).busy.value,
							"data-testid": "showroom-channel-delete",
							onClick: oe
						}, {
							default: k(() => [b(E(D(B)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						x(e, { action: D(U) }, null, 8, ["action"]),
						x(l, null, {
							header: k(() => [y("h2", R, E(D(B)("showroom.structure.details")), 1)]),
							default: k(() => [y("form", {
								class: "flex flex-col gap-4",
								"data-testid": "showroom-channel-form",
								onSubmit: A(ae, ["prevent"])
							}, [
								x(s, {
									label: D(B)("showroom.catalog.fields.label_in", { locale: D(V) }),
									error: D(U).error("labels")
								}, {
									default: k(() => [x(a, {
										modelValue: D(Q),
										"onUpdate:modelValue": r[0] ||= (e) => C(Q) ? Q.value = e : null,
										class: "w-64",
										"data-testid": "showroom-channel-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								x(c, {
									modelValue: D($),
									"onUpdate:modelValue": r[1] ||= (e) => C($) ? $.value = e : null,
									locales: D(re),
									trees: D(ie),
									action: D(U),
									testid: "showroom-channel"
								}, null, 8, [
									"modelValue",
									"locales",
									"trees",
									"action"
								]),
								y("div", null, [x(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: D(U).busy.value,
									"data-testid": "showroom-channel-save"
								}, {
									default: k(() => [b(E(D(B)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						y("p", z, E(D(B)("showroom.structure.channels.delete_note")), 1)
					])) : _("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { B as default };
